<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Models\Puskesmas;
use App\Models\Province;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\TreatmentType;
use App\Models\PatientTreatment;
use App\Models\PatientMedicationSchedule;
use App\Models\Screening;
use App\Models\ClinicalExamination;
use App\Models\CloseContact;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $puskesmasFilter = $request->query('puskesmas_id');
        $subdistrictFilter = $request->query('subdistrict_id');
        $genderFilter = $request->query('gender');
        $statusFilter = $request->query('treatment_status');
        $keyword = $request->query('q');

        $query = Patient::with(['user', 'puskesmas', 'subdistrict.district.province', 'village', 'treatments.treatmentType']);

        if ($puskesmasFilter) {
            $query->where('puskesmas_id', $puskesmasFilter);
        }

        if ($subdistrictFilter) {
            $query->where('subdistrict_id', $subdistrictFilter);
        }

        if ($genderFilter) {
            $query->whereHas('user', function($q) use ($genderFilter) {
                $q->where('gender', $genderFilter);
            });
        }

        if ($statusFilter) {
            $query->whereHas('treatments', function($q) use ($statusFilter) {
                $q->where('treatment_status', $statusFilter);
            });
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nik', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%")
                  ->orWhere('occupation', 'like', "%{$keyword}%")
                  ->orWhereHas('user', function ($sub) use ($keyword) {
                      $sub->where('name', 'like', "%{$keyword}%")
                          ->orWhere('username', 'like', "%{$keyword}%")
                          ->orWhere('phone', 'like', "%{$keyword}%")
                          ->orWhere('email', 'like', "%{$keyword}%");
                  });
            });
        }

        $patients = $query->orderByDesc('id')->get();
        $puskesmasList = Puskesmas::orderBy('name')->get();
        $subdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.patients.index', compact('patients', 'puskesmasList', 'subdistricts', 'puskesmasFilter', 'subdistrictFilter', 'genderFilter', 'statusFilter', 'keyword'))
            ->with([
                'title'        => 'Data Pasien TB',
                'pageTitle'    => 'Data Pasien Tuberkulosis',
                'pageSubtitle' => 'Daftar rekam medis dan data demografi pasien TB yang terdaftar pada faskes dan wilayah.'
            ]);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $patient = Patient::with([
            'user',
            'puskesmas.subdistrict.district',
            'subdistrict.district.province',
            'village',
            'treatments.treatmentType',
            'treatments.visits',
            'treatments.medicationRecords' => function($q) {
                $q->orderByDesc('id')->limit(30);
            },
            'medicationSchedule',
            'screenings' => function($q) {
                $q->orderByDesc('id');
            },
            'examinations' => function($q) {
                $q->orderByDesc('examination_date');
            },
            'closeContacts' => function($q) {
                $q->orderByDesc('id');
            }
        ])->findOrFail($id);

        // Calculate BMI and Nutritional Status
        $bmi = null;
        $bmiStatus = '-';
        if ($patient->height && $patient->weight && $patient->height > 0) {
            $heightInMeters = $patient->height / 100;
            $bmi = round($patient->weight / ($heightInMeters * $heightInMeters), 1);
            if ($bmi < 18.5) {
                $bmiStatus = 'Berat Badan Kurang / Malnutrisi';
            } elseif ($bmi <= 22.9) {
                $bmiStatus = 'Normal / Ideal';
            } elseif ($bmi <= 24.9) {
                $bmiStatus = 'Kelebihan Berat Badan (Overweight)';
            } else {
                $bmiStatus = 'Obesitas';
            }
        }

        // Active treatment
        $activeTreatment = $patient->treatments->where('treatment_status', 'Berjalan')->first() 
            ?? $patient->treatments->first();

        // Calculate age
        $age = '-';
        if ($patient->user && $patient->user->date_of_birth) {
            $age = Carbon::parse($patient->user->date_of_birth)->age . ' Tahun';
        }

        return view('admin.patients.show', compact('patient', 'bmi', 'bmiStatus', 'activeTreatment', 'age'))
            ->with([
                'title'        => 'Detail Pasien: ' . optional($patient->user)->name,
                'pageTitle'    => 'Rekam Pasien: ' . optional($patient->user)->name,
                'pageSubtitle' => 'Detail klinis komprehensif, kepatuhan minum obat, jadwal kontrol, dan riwayat pemeriksaan TB.'
            ]);
    }

    public function form(?string $encryptedId = null)
    {
        $patient = null;
        $isEdit = false;
        $selectedProvinceId = null;
        $selectedDistrictId = null;

        $provinces = Province::orderBy('name')->get();

        if ($encryptedId) {
            $id = decrypt_id($encryptedId);
            $patient = Patient::with(['user', 'subdistrict.district.province', 'treatments.treatmentType'])->findOrFail($id);
            $isEdit = true;

            $selectedProvinceId = optional(optional(optional($patient->subdistrict)->district)->province)->id;
            $selectedDistrictId = optional(optional($patient->subdistrict)->district)->id;
        } else {
            $patient = new Patient();
        }

        $selectedProvinceId = old('province_id', $selectedProvinceId);
        $selectedDistrictId = old('district_id', $selectedDistrictId);
        $selectedSubdistrictId = old('subdistrict_id', $patient->subdistrict_id);

        $districts = $selectedProvinceId 
            ? District::where('province_id', $selectedProvinceId)->orderBy('name')->get() 
            : District::orderBy('name')->get();

        $subdistricts = $selectedDistrictId 
            ? Subdistrict::where('district_id', $selectedDistrictId)->orderBy('name')->get() 
            : Subdistrict::orderBy('name')->get();

        $villages = $selectedSubdistrictId 
            ? Village::where('subdistrict_id', $selectedSubdistrictId)->orderBy('name')->get() 
            : Village::orderBy('name')->get();

        $puskesmas = Puskesmas::orderBy('name')->get();
        $treatmentTypes = TreatmentType::all();

        return view('admin.patients.form', compact(
            'patient', 'puskesmas', 'provinces', 'districts', 'subdistricts', 'villages', 
            'selectedProvinceId', 'selectedDistrictId', 'treatmentTypes', 'isEdit'
        ))->with([
            'title'        => $isEdit ? ('Edit Pasien: ' . optional($patient->user)->name) : 'Tambah Pasien TB',
            'pageTitle'    => $isEdit ? ('Edit Rekam Medis: ' . optional($patient->user)->name) : 'Pendaftaran Pasien TB Baru',
            'pageSubtitle' => $isEdit ? 'Perbarui data identitas, alamat, fisik, dan Puskesmas pembina.' : 'Formulir registrasi rekam medis pasien tuberkulosis dan penugasan Puskesmas.',
        ]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($id)
    {
        return $this->form($id);
    }

    public function save(Request $request, ?string $encryptedId = null)
    {
        $rawId = $request->input('encrypted_id') ?? $encryptedId;
        $patient = null;
        $isUpdate = false;

        if ($rawId) {
            $id = decrypt_id($rawId);
            $patient = Patient::with('user')->findOrFail($id);
            $isUpdate = true;
        }

        $request->validate([
            'name'                 => 'required|string|max:255',
            'nik'                  => ['required', 'string', 'size:16', $isUpdate ? Rule::unique('patients')->ignore($patient->id) : 'unique:patients,nik'],
            'username'             => [$isUpdate ? 'nullable' : 'required', 'string', 'max:50', $isUpdate ? Rule::unique('users')->ignore($patient->user_id) : 'unique:users,username'],
            'phone'                => 'nullable|string|max:20',
            'gender'               => 'required|in:L,P',
            'date_of_birth'        => 'nullable|date',
            'place_of_birth'       => 'nullable|string|max:100',
            'address'              => 'required|string',
            'subdistrict_id'       => 'required|exists:subdistricts,id',
            'village_id'           => 'nullable|exists:villages,id',
            'rw'                   => 'nullable|string|max:5',
            'rt'                   => 'nullable|string|max:5',
            'puskesmas_id'         => 'required|exists:puskesmas,id',
            'occupation'           => 'nullable|string|max:100',
            'height'               => 'nullable|numeric|min:30|max:250',
            'weight'               => 'nullable|numeric|min:2|max:300',
            'blood_type'           => 'nullable|in:A,B,AB,O',
            'diagnosis_date'       => 'nullable|date',
            'treatment_start_date' => 'nullable|date',
            'treatment_type_id'    => 'nullable|exists:treatment_types,id',
        ], [
            'name.required'           => 'Nama lengkap pasien wajib diisi.',
            'nik.required'            => 'NIK wajib diisi.',
            'nik.size'                => 'NIK harus terdiri dari 16 digit.',
            'nik.unique'              => 'NIK ini sudah terdaftar pada database pasien.',
            'username.required'       => 'Username wajib diisi.',
            'username.unique'         => 'Username telah digunakan.',
            'address.required'        => 'Alamat lengkap wajib diisi.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
            'puskesmas_id.required'   => 'Puskesmas wajib dipilih.',
        ]);

        return DB::transaction(function () use ($request, $patient, $isUpdate) {
            if ($isUpdate) {
                // Update User
                if ($patient->user) {
                    $patient->user->update([
                        'name'           => $request->name,
                        'phone'          => $request->phone,
                        'gender'         => $request->gender,
                        'place_of_birth' => $request->place_of_birth,
                        'date_of_birth'  => $request->date_of_birth,
                    ]);
                }

                // Update Patient
                $patient->update([
                    'nik'                  => $request->nik,
                    'address'              => $request->address,
                    'subdistrict_id'       => $request->subdistrict_id,
                    'village_id'           => $request->village_id,
                    'rw'                   => $request->rw,
                    'rt'                   => $request->rt,
                    'occupation'           => $request->occupation,
                    'height'               => $request->height,
                    'weight'               => $request->weight,
                    'blood_type'           => $request->blood_type,
                    'diagnosis_date'       => $request->diagnosis_date,
                    'treatment_start_date' => $request->treatment_start_date,
                    'puskesmas_id'         => $request->puskesmas_id,
                ]);

                ActivityLog::log('Perbarui Data Pasien', 'Pasien', "Memperbarui data rekam medis pasien {$request->name} (NIK: {$request->nik}).");

                return redirect()->route('admin.patients.show', $patient->encrypted_id)->with('success', 'Data rekam medis pasien berhasil diperbarui.');
            } else {
                // 1. Create User
                $user = User::create([
                    'name'           => $request->name,
                    'username'       => $request->username,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'gender'         => $request->gender,
                    'place_of_birth' => $request->place_of_birth,
                    'date_of_birth'  => $request->date_of_birth,
                    'password'       => Hash::make('password123'), // Default patient password
                    'user_type_id'   => 2, // Pasien
                    'is_active'      => 1,
                ]);

                // 2. Create Patient Record
                $patient = Patient::create([
                    'nik'                  => $request->nik,
                    'user_id'              => $user->id,
                    'address'              => $request->address,
                    'subdistrict_id'       => $request->subdistrict_id,
                    'village_id'           => $request->village_id,
                    'rw'                   => $request->rw,
                    'rt'                   => $request->rt,
                    'occupation'           => $request->occupation,
                    'height'               => $request->height,
                    'weight'               => $request->weight,
                    'blood_type'           => $request->blood_type,
                    'diagnosis_date'       => $request->diagnosis_date,
                    'treatment_start_date' => $request->treatment_start_date ?? Carbon::now()->format('Y-m-d'),
                    'puskesmas_id'         => $request->puskesmas_id,
                ]);

                // 3. Create initial Patient Treatment if regimen selected
                if ($request->treatment_type_id) {
                    $tType = TreatmentType::find($request->treatment_type_id);
                    $startDate = Carbon::parse($request->treatment_start_date ?? now());
                    $months = ($tType && $tType->duration_unit === 'month') ? $tType->treatment_duration : 6;
                    $endDate = (clone $startDate)->addMonths($months);

                    PatientTreatment::create([
                        'patient_id'        => $patient->id,
                        'treatment_type_id' => $request->treatment_type_id,
                        'diagnosis_date'    => $request->diagnosis_date ?? $startDate->format('Y-m-d'),
                        'start_date'        => $startDate->format('Y-m-d'),
                        'end_date'          => $endDate->format('Y-m-d'),
                        'treatment_days'    => $startDate->diffInDays($endDate),
                        'medication_time'   => '08:00:00',
                        'prescription'      => 'Regimen Standar ' . ($tType->treatment_type ?? 'TB'),
                        'treatment_status'  => 'Berjalan',
                    ]);
                }

                ActivityLog::log('Tambah Pasien TB', 'Pasien', "Mendaftarkan pasien baru {$user->name} NIK {$patient->nik} di Puskesmas ID {$patient->puskesmas_id}.");

                return redirect()->route('admin.patients.show', $patient->encrypted_id)->with('success', 'Data pasien TB berhasil didaftarkan.');
            }
        });
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $patient = Patient::with('user')->findOrFail($id);
        $user = $patient->user;
        $name = optional($user)->name ?? 'Pasien #' . $id;

        DB::transaction(function () use ($patient, $user) {
            // 1. Delete treatments and related medication records / visits
            if (method_exists($patient, 'treatments')) {
                $patient->treatments()->each(function ($treatment) {
                    $treatment->medicationRecords()->delete();
                    $treatment->visits()->delete();
                    $treatment->delete();
                });
            }

            // 2. Delete clinical examinations
            if (method_exists($patient, 'examinations')) {
                $patient->examinations()->delete();
            }
            if (method_exists($patient, 'clinicalExaminations')) {
                $patient->clinicalExaminations()->delete();
            }

            // 3. Delete close contacts
            if (method_exists($patient, 'closeContacts')) {
                $patient->closeContacts()->delete();
            }

            // 4. Delete medication reminder schedules
            PatientMedicationSchedule::where('patient_id', $patient->id)->delete();

            // 5. Unlink any screenings tied to this patient
            Screening::where('patient_id', $patient->id)->update(['patient_id' => null]);

            // 6. Delete patient record
            $patient->delete();

            // 7. If linked user account is exclusively a patient (user_type_id = 2), delete the user account
            if ($user && $user->user_type_id == 2) {
                Screening::where('user_id', $user->id)->update(['user_id' => null]);
                $user->delete();
            }
        });

        ActivityLog::log('Hapus Pasien', 'Pasien', "Menghapus data pasien {$name}.");

        return redirect()->route('admin.patients.index')->with('success', "Data pasien {$name} berhasil dihapus.");
    }
}
