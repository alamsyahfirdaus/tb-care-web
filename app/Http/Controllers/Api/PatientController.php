<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientMedicationSchedule;
use App\Models\PatientTreatment;
use App\Models\TreatmentVisit;
use App\Models\MedicationRecord;
use App\Models\Consultation;
use App\Models\EducationalMaterial;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use App\Policies\PatientPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        // Ambil filter status pengobatan dari request (jika ada)
        $treatmentStatus = $request->post('treatment_status') ?: $request->get('treatment_status');

        // Ambil data user yang sedang login
        $user = Auth::user();

        // Validasi akses awal
        if (!in_array($user->user_type_id, [1, 2, 3])) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat data pasien.'
            ], 403);
        }

        // Siapkan query dengan centralized scope accessibleBy
        $patientsQuery = Patient::accessibleBy($user)->with([
            'user',
            'puskesmas',
            'village',
            'subdistrict.district.province',
            'treatments' => function ($query) use ($treatmentStatus) {
                $query->when($treatmentStatus, function ($q) use ($treatmentStatus) {
                    $q->where('treatment_status', $treatmentStatus);
                })
                    ->orderByDesc('start_date')
                    ->with([
                        'visits' => function ($q) {
                            $q->orderByDesc('visit_date');
                        }
                    ]);
            },
        ]);

        // Filter pencarian nama / NIK jika disediakan
        if ($request->filled('search')) {
            $search = $request->input('search');
            $patientsQuery->where(function ($q) use ($search) {
                $q->where('nik', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $patients = $patientsQuery->get();

        // Mapping data pasien menjadi format array JSON
        $patientsData = $patients->map(function ($patient) {
            $villageName     = optional($patient->village)->name;
            $subdistrictName = optional($patient->subdistrict)->name;
            $districtName    = optional($patient->subdistrict?->district)->name;
            $provinceName    = optional($patient->subdistrict?->district?->province)->name;

            return [
                'id'             => $patient->id,
                'user_id'        => $patient->user_id,
                'nik'            => $patient->nik,
                'address'        => $patient->address,
                'puskesmas_id'   => $patient->puskesmas_id,
                'subdistrict_id' => $patient->subdistrict_id,
                'village_id'     => $patient->village_id,
                'village_name'   => $villageName,
                'rw'             => $patient->rw,
                'rt'             => $patient->rt,
                'occupation'     => $patient->occupation,
                'height'         => $patient->height,
                'weight'         => $patient->weight,
                'blood_type'     => $patient->blood_type,
                'diagnosis_date' => $patient->diagnosis_date,
                'treatment_start_date' => $patient->treatment_start_date ? \Carbon\Carbon::parse($patient->treatment_start_date)->format('Y-m-d') : null,
                'medication_schedule' => $this->resolveMedicationSchedule($patient),

                'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                    ? "$subdistrictName, $districtName, $provinceName"
                    : null,

                'name'           => optional($patient->user)->name,
                'email'          => optional($patient->user)->email,
                'phone'          => optional($patient->user)->phone,
                'gender'         => optional($patient->user)->gender,
                'place_of_birth' => optional($patient->user)->place_of_birth,
                'date_of_birth'  => optional($patient->user)->date_of_birth,

                'puskesmas'      => optional($patient->puskesmas)->name,

                'treatments'     => $patient->treatments ? $patient->treatments->map(function ($treatment) {
                    return [
                        'id'                => $treatment->id,
                        'treatment_type_id' => $treatment->treatment_type_id,
                        'treatment_status'  => $treatment->treatment_status,
                        'diagnosis_date'    => $treatment->diagnosis_date,
                        'start_date'        => $treatment->start_date,
                        'end_date'          => $treatment->end_date,
                        'treatment_days'    => $treatment->treatment_days,
                        'medication_time'   => $treatment->medication_time,

                        'visits' => $treatment->visits ? $treatment->visits->map(function ($visit) {
                            return [
                                'id'           => $visit->id,
                                'visit_date'   => $visit->visit_date,
                                'visit_time'   => $visit->visit_time,
                                'visit_status' => $visit->visit_status,
                                'notes'        => $visit->notes,
                            ];
                        }) : [],
                        'prescription'     => $this->normalizePrescriptionNames($treatment->prescription),
                        'medications'      => $this->normalizePrescription($treatment->prescription, $treatment->medication_time),
                    ];
                }) : [],
            ];
        });

        return response()->json([
            'message' => 'Data pasien berhasil diambil.',
            'data'    => $patientsData
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isUpdate = $request->filled('patient_id');

        // 1. Otorisasi create/update awal
        $existingPatient = null;
        if ($isUpdate) {
            $existingPatient = Patient::with('user')->find($request->patient_id);
            if (!$existingPatient) {
                return response()->json([
                    'message' => 'Data pasien tidak ditemukan.'
                ], 404);
            }

            if (!$existingPatient->isAccessibleBy($user)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang mengubah data pasien di luar wilayah binaan Anda.'
                ], 403);
            }
        } else {
            if (!in_array($user->user_type_id, [1, 3])) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk menambahkan pasien.'
                ], 403);
            }
        }

        $existingUser = $existingPatient?->user;

        // 2. Validasi input
        $validated = $request->validate([
            'nik' => [
                'nullable',
                'digits:16',
                Rule::unique('patients', 'nik')->ignore($existingPatient ? $existingPatient->id : null),
            ],
            'name'             => 'required|string|max:255',
            'email'            => ['nullable', 'email', Rule::unique('users', 'email')->ignore($existingUser ? $existingUser->id : null)],
            'phone'            => ['required', 'string', 'min:10', 'max:15', Rule::unique('users', 'phone')->ignore($existingUser ? $existingUser->id : null)],
            'gender'           => 'required|in:L,P',
            'place_of_birth'   => 'required|string|max:100',
            'date_of_birth'    => 'required|date',
            'puskesmas_id'     => 'required|exists:puskesmas,id',
            'subdistrict_id'   => 'nullable|exists:subdistricts,id',
            'village_id'       => 'nullable|exists:villages,id',
            'rw'               => 'nullable|string|max:5',
            'rt'               => 'nullable|string|max:5',
            'address'          => 'nullable|string',
            'occupation'       => 'nullable|string',
            'height'           => 'nullable|integer',
            'weight'           => 'nullable|integer',
            'blood_type'       => 'nullable|string|max:3',
            'diagnosis_date'   => 'nullable|date',
            'treatment_start_date' => 'nullable|date|before_or_equal:today',
        ], [
            'nik.required'             => 'NIK wajib diisi.',
            'nik.digits'               => 'NIK harus terdiri dari 16 digit.',
            'nik.unique'               => 'NIK sudah terdaftar.',
            'name.required'            => 'Nama wajib diisi.',
            'email.required'           => 'Email wajib diisi.',
            'email.email'              => 'Format email tidak valid.',
            'email.unique'             => 'Email sudah digunakan oleh pengguna lain.',
            'phone.required'           => 'Nomor HP wajib diisi.',
            'phone.min'                => 'Nomor HP minimal 10 digit.',
            'phone.max'                => 'Nomor HP maksimal 15 digit.',
            'phone.unique'             => 'Nomor HP sudah digunakan oleh pengguna lain.',
            'gender.required'          => 'Jenis kelamin wajib dipilih.',
            'gender.in'                => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
            'place_of_birth.required'  => 'Tempat lahir wajib diisi.',
            'date_of_birth.required'   => 'Tanggal lahir wajib diisi.',
            'treatment_start_date.required' => 'Tanggal mulai pengobatan wajib diisi.',
            'treatment_start_date.date'     => 'Format tanggal mulai pengobatan tidak valid.',
            'treatment_start_date.before_or_equal' => 'Tanggal mulai pengobatan tidak boleh di masa depan.',
            'puskesmas_id.required'    => 'Puskesmas wajib dipilih.',
            'puskesmas_id.exists'      => 'Puskesmas tidak ditemukan.',
        ]);

        // 3. Verifikasi Wilayah Wewenang untuk Petugas
        if ($user->user_type_id == 3 && $user->officer) {
            if ($user->officer->isKader() || $user->officer->isPJTB()) {
                if ($user->officer->puskesmas_id) {
                    $validated['puskesmas_id'] = $user->officer->puskesmas_id;
                }
            }

            $policy = app(PatientPolicy::class);
            $hasAuthority = $policy->canAssignTerritory(
                $user,
                $validated['village_id'] ?? null,
                $validated['rw'] ?? null,
                $validated['rt'] ?? null,
                $validated['puskesmas_id'] ?? null
            );

            if (!$hasAuthority) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang menugaskan pasien ke wilayah atau faskes tersebut.'
                ], 403);
            }
        }

        // 4-7. Simpan user dan pasien dalam satu transaksi database atomik
        DB::transaction(function () use (
            &$existingUser,
            &$existingPatient,
            $validated
        ) {
            // 4. Jika user akun pasien belum ada, buat user baru
            if (!$existingUser) {
                $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $validated['email'] ?? $validated['phone'])[0]);
                $username = $baseUsername ?: 'user';
                $counter = 1;

                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter++;
                }

                $existingUser = new User([
                    'username' => $username,
                    'password' => Hash::make($username),
                ]);
            }

            // 5. Simpan data user
            $existingUser->fill([
                'name'           => $validated['name'],
                'email'          => $validated['email'] ?? null,
                'phone'          => $validated['phone'],
                'gender'         => $validated['gender'],
                'place_of_birth' => $validated['place_of_birth'],
                'date_of_birth'  => $validated['date_of_birth'],
                'user_type_id'   => 2, // 2 = Pasien
                'is_active'      => true,
            ]);
            $existingUser->save();

            // 6. Jika pasien belum ada, buat data pasien baru
            if (!$existingPatient) {
                $existingPatient = new Patient([
                    'user_id' => $existingUser->id,
                ]);
            }

            // Sinkronisasi subdistrict_id jika village_id diberikan
            $subdistrictId = $validated['subdistrict_id'] ?? null;
            if (!empty($validated['village_id'])) {
                $village = Village::find($validated['village_id']);
                if ($village && $village->subdistrict_id) {
                    $subdistrictId = $village->subdistrict_id;
                }
            }

            // 7. Simpan atau perbarui data pasien
            $existingPatient->fill([
                'nik'             => $validated['nik'] ?? null,
                'address'         => $validated['address'] ?? null,
                'subdistrict_id'  => $subdistrictId,
                'village_id'      => $validated['village_id'] ?? null,
                'rw'              => $validated['rw'] ?? null,
                'rt'              => $validated['rt'] ?? null,
                'occupation'      => $validated['occupation'] ?? null,
                'height'          => $validated['height'] ?? null,
                'weight'          => $validated['weight'] ?? null,
                'blood_type'      => $validated['blood_type'] ?? null,
                'diagnosis_date'  => $validated['diagnosis_date'] ?? null,
                'treatment_start_date' => array_key_exists('treatment_start_date', $validated) ? $validated['treatment_start_date'] : ($existingPatient?->treatment_start_date ?? null),
                'puskesmas_id'    => $validated['puskesmas_id'],
            ]);
            $existingPatient->save();
        });

        $message = $isUpdate
            ? 'Data pasien berhasil diperbarui.'
            : 'Data pasien baru berhasil ditambahkan.';

        return response()->json([
            'message' => $message,
            'data'    => $existingPatient->load(['user', 'village', 'subdistrict', 'puskesmas']),
        ], $isUpdate ? 200 : 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        // Ambil data pasien berdasarkan ID beserta relasi terkait
        $patient = Patient::with([
            'user',
            'puskesmas',
            'village',
            'subdistrict.district.province',
            'treatments' => function ($query) {
                $query->orderByDesc('start_date')
                    ->with([
                        'visits' => function ($q) {
                            $q->orderBy('visit_date', 'asc')->orderBy('visit_time', 'asc');
                        }
                    ]);
            }
        ])->find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Proteksi IDOR: Cek otorisasi terpusat
        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $villageName     = optional($patient->village)->name;
        $subdistrictName = optional($patient->subdistrict)->name;
        $districtName    = optional($patient->subdistrict?->district)->name;
        $provinceName    = optional($patient->subdistrict?->district?->province)->name;

        $puskesmasName = optional($patient->puskesmas)->name ?? 'Puskesmas';

        $patientData = [
            'id'             => $patient->id,
            'user_id'        => $patient->user_id,
            'nik'            => $patient->nik,
            'address'        => $patient->address,
            'puskesmas_id'   => $patient->puskesmas_id,
            'subdistrict_id' => $patient->subdistrict_id,
            'village_id'     => $patient->village_id,
            'village_name'   => $villageName,
            'rw'             => $patient->rw,
            'rt'             => $patient->rt,
            'occupation'     => $patient->occupation,
            'height'         => $patient->height,
            'weight'         => $patient->weight,
            'blood_type'     => $patient->blood_type,
            'diagnosis_date' => $patient->diagnosis_date,
            'treatment_start_date' => $patient->treatment_start_date ? \Carbon\Carbon::parse($patient->treatment_start_date)->format('Y-m-d') : null,
            'medication_schedule' => $this->resolveMedicationSchedule($patient),

            'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                ? "$subdistrictName, $districtName, $provinceName"
                : null,

            'name'           => optional($patient->user)->name,
            'email'          => optional($patient->user)->email,
            'phone'          => optional($patient->user)->phone,
            'gender'         => optional($patient->user)->gender,
            'place_of_birth' => optional($patient->user)->place_of_birth,
            'date_of_birth'  => optional($patient->user)->date_of_birth,
            'close_contacts_count' => $patient->closeContacts()->count(),

            'puskesmas'      => $puskesmasName,

            'treatments'     => $patient->treatments ? $patient->treatments->map(function ($treatment) use ($puskesmasName) {
                return [
                    'id'                => $treatment->id,
                    'treatment_type_id' => $treatment->treatment_type_id,
                    'treatment_status'  => $treatment->treatment_status,
                    'diagnosis_date'    => $treatment->diagnosis_date,
                    'start_date'        => $treatment->start_date,
                    'end_date'          => $treatment->end_date,
                    'treatment_days'    => $treatment->treatment_days,
                    'medication_time'   => $treatment->medication_time,

                    'visits' => $treatment->visits ? $treatment->visits->map(function ($visit) use ($puskesmasName) {
                        return [
                            'id'             => $visit->id,
                            'visit_date'     => $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : null,
                            'visit_time'     => $visit->visit_time,
                            'visit_status'   => $visit->visit_status,
                            'notes'          => $visit->notes,
                            'puskesmas_name' => $puskesmasName,
                        ];
                    }) : [],
                    'prescription'     => $this->normalizePrescriptionNames($treatment->prescription),
                    'medications'      => $this->normalizePrescription($treatment->prescription, $treatment->medication_time),
                ];
            }) : [],
        ];

        return response()->json([
            'message' => 'Detail data pasien berhasil diambil.',
            'data'    => $patientData
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $patient = Patient::with('user')->find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Proteksi IDOR: Cek otorisasi terpusat
        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang menghapus pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $patientUser = $patient->user;

        if ($patientUser && $patientUser->photo) {
            $filePath = 'images/' . $patientUser->photo;
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        if ($patientUser) {
            $patientUser->delete();
        } else {
            $patient->delete();
        }

        return response()->json([
            'message' => 'Data pasien berhasil dihapus.'
        ]);
    }

    public function treatmentHistory($id)
    {
        $user = Auth::user();
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Proteksi IDOR: Cek otorisasi
        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses riwayat pengobatan pasien ini.'
            ], 403);
        }

        $treatments = PatientTreatment::with('treatmentType')
            ->where('patient_id', $id)
            ->orderByDesc('start_date')
            ->get();

        $treatmentHistory = $treatments->map(function ($treatment) {
            $normalizedMedications = $this->normalizePrescription($treatment->prescription, $treatment->medication_time);
            $normalizedNames = $this->normalizePrescriptionNames($treatment->prescription);

            return [
                'id'               => $treatment->id,
                'patient_id'       => $treatment->patient_id,
                'treatment_type'   => optional($treatment->treatmentType)->treatment_type,
                'start_date'       => $treatment->start_date,
                'end_date'         => $treatment->end_date,
                'treatment_days'   => $treatment->treatment_days,
                'medication_time'  => $treatment->medication_time,
                'treatment_status' => $treatment->treatment_status,
                'prescription'     => $normalizedNames,
                'medications'      => $normalizedMedications,
            ];
        });

        return response()->json([
            'message' => 'Riwayat pengobatan berhasil diambil.',
            'data'    => $treatmentHistory
        ]);
    }

    public function treatmentAdherence()
    {
        $user = Auth::user();

        // 1. Pasien mandiri
        if ($user->user_type_id == 2) {
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'message' => 'Data pasien tidak ditemukan.'
                ], 404);
            }

            $treatment = PatientTreatment::where('patient_id', $patient->id)
                ->with('treatmentType')
                ->withCount([
                    'medicationRecords as verified_count' => fn($q) =>
                    $q->where('is_verified', true)
                ])
                ->orderByDesc('id')
                ->first();

            if (!$treatment) {
                return response()->json([
                    'message' => 'Belum ada data pengobatan.',
                    'data' => [
                        'total_treatment'     => 0,
                        'total_expected_days' => 0,
                        'total_verified_days' => 0,
                        'adherence_average'   => '0%',
                    ]
                ]);
            }

            $expected = $treatment->treatment_days ?? 0;
            $verified = $treatment->verified_count;
            $percentage = $expected > 0 ? round(($verified / $expected) * 100, 2) : 0;

            return response()->json([
                'message' => 'Tingkat kepatuhan pasien berhasil dihitung.',
                'data' => [
                    'total_treatment'     => 1,
                    'total_expected_days' => $expected,
                    'total_verified_days' => $verified,
                    'adherence_average'   => $percentage . '%',
                ]
            ]);
        }

        // 2. Admin & Petugas (Scoped to accessible patients)
        $patientsQuery = Patient::accessibleBy($user);
        $patientIds = $patientsQuery->pluck('id');

        $latestTreatments = collect();

        foreach ($patientIds as $pid) {
            $treatment = PatientTreatment::where('patient_id', $pid)
                ->orderByDesc('id')
                ->withCount([
                    'medicationRecords as verified_count' => fn($q) =>
                    $q->where('is_verified', true)
                ])
                ->first();

            if ($treatment) {
                $latestTreatments->push($treatment);
            }
        }

        if ($latestTreatments->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data pengobatan terakhir yang tersedia.',
                'data' => [
                    'total_treatment'     => 0,
                    'total_expected_days' => 0,
                    'total_verified_days' => 0,
                    'adherence_average'   => '0%',
                ]
            ]);
        }

        $totalExpected = $latestTreatments->sum('treatment_days');
        $totalVerified = $latestTreatments->sum('verified_count');
        $percentage = $totalExpected > 0 ? round(($totalVerified / $totalExpected) * 100, 2) : 0;

        return response()->json([
            'message' => 'Rata-rata tingkat kepatuhan dari pengobatan terakhir pasien berhasil dihitung.',
            'data' => [
                'total_treatment'     => $latestTreatments->count(),
                'total_expected_days' => $totalExpected,
                'total_verified_days' => $totalVerified,
                'adherence_average'   => $percentage . '%',
            ]
        ]);
    }

    /**
     * Selesaikan data jadwal minum obat secara aman dari tabel khusus atau riwayat treatment.
     * Tidak akan memicu QueryException meskipun tabel belum di-migrate di database production.
     */
    private function resolveMedicationSchedule(Patient $patient): ?array
    {
        try {
            if (Schema::hasTable('patient_medication_schedules')) {
                $schedule = $patient->medicationSchedule;
                if ($schedule) {
                    return [
                        'id'            => $schedule->id,
                        'patient_id'    => $schedule->patient_id,
                        'reminder_time' => $schedule->reminder_time,
                        'is_active'     => (bool) $schedule->is_active,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error resolving medication_schedule: ' . $e->getMessage());
        }

        // Fallback: periksa medication_time pada riwayat treatment pasien jika belum ada jadwal khusus
        try {
            $treatment = null;
            if ($patient->relationLoaded('treatments') && $patient->treatments) {
                $treatment = $patient->treatments->first();
            } else {
                $treatment = $patient->treatments()->orderByDesc('start_date')->first();
            }

            if ($treatment && !empty($treatment->medication_time)) {
                return [
                    'id'            => $treatment->id,
                    'patient_id'    => $patient->id,
                    'reminder_time' => $treatment->medication_time,
                    'is_active'     => true,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Error resolving medication_time from treatment: ' . $e->getMessage());
        }

        return null;
    }

    public function getMedicationSchedule(Request $request, $id = null)
    {
        $user = Auth::user();
        $patientId = $id ?? $request->input('patient_id');
        if (!$patientId && $user && $user->user_type_id == 2) {
            $patientId = optional($user->patient)->id;
        }
        $patient = Patient::find($patientId);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
            ], 403);
        }

        $scheduleData = $this->resolveMedicationSchedule($patient);

        return response()->json([
            'success' => true,
            'data'    => $scheduleData,
        ]);
    }

    public function saveMedicationSchedule(Request $request, $id = null)
    {
        $user = Auth::user();
        $patientId = $id ?? $request->input('patient_id');
        if (!$patientId && $user && $user->user_type_id == 2) {
            $patientId = optional($user->patient)->id;
        }
        $patient = Patient::find($patientId);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reminder_time' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'is_active'     => 'nullable|boolean',
        ], [
            'reminder_time.required' => 'Waktu pengingat minum obat wajib diisi.',
            'reminder_time.regex'    => 'Format waktu pengingat minum obat tidak valid. Gunakan format JJ:MM.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $time = $request->reminder_time;
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        $savedSchedule = null;

        // 1. Simpan ke patient_medication_schedules jika tabel tersedia
        try {
            if (Schema::hasTable('patient_medication_schedules')) {
                $schedule = PatientMedicationSchedule::where('patient_id', $patient->id)->first();
                if ($schedule) {
                    $schedule->reminder_time = $time;
                    if ($request->has('is_active')) {
                        $schedule->is_active = $request->boolean('is_active');
                    } else {
                        $schedule->is_active = true;
                    }
                    $schedule->save();
                } else {
                    $schedule = PatientMedicationSchedule::create([
                        'patient_id'    => $patient->id,
                        'reminder_time' => $time,
                        'is_active'     => $request->has('is_active') ? $request->boolean('is_active') : true,
                    ]);
                }
                $savedSchedule = [
                    'id'            => $schedule->id,
                    'patient_id'    => $schedule->patient_id,
                    'reminder_time' => $schedule->reminder_time,
                    'is_active'     => (bool) $schedule->is_active,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Error saving to patient_medication_schedules: ' . $e->getMessage());
        }

        // 2. Selaraskan juga ke active patient_treatments jika pasien memiliki record treatment
        try {
            $activeTreatment = $patient->treatments()
                ->where('treatment_status', 'Berjalan')
                ->orderByDesc('start_date')
                ->first();
            if ($activeTreatment) {
                $activeTreatment->medication_time = $time;
                $activeTreatment->save();
            }

            // Jika tabel patient_medication_schedules belum ada, fallback ke treatment
            if (!$savedSchedule && $activeTreatment) {
                $savedSchedule = [
                    'id'            => $activeTreatment->id,
                    'patient_id'    => $patient->id,
                    'reminder_time' => $time,
                    'is_active'     => true,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Error syncing medication_time to treatment: ' . $e->getMessage());
        }

        if (!$savedSchedule) {
            $savedSchedule = [
                'id'            => null,
                'patient_id'    => $patient->id,
                'reminder_time' => $time,
                'is_active'     => true,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal minum obat berhasil disimpan.',
            'data'    => $savedSchedule,
        ]);
    }

    public function storeMedicationSchedule(Request $request, $id = null)
    {
        return $this->saveMedicationSchedule($request, $id);
    }

    public function updateMedicationSchedule(Request $request, $id = null)
    {
        return $this->saveMedicationSchedule($request, $id);
    }

    public function saveSchedule(Request $request, $id = null)
    {
        return $this->saveMedicationSchedule($request, $id);
    }

    public function storeSchedule(Request $request, $id = null)
    {
        return $this->saveMedicationSchedule($request, $id);
    }

    /**
     * Dashboard / Beranda Pasien Terpadu
     * Mengembalikan data komprehensif pasien untuk beranda dalam 1 kali request efisien.
     */
    public function home(Request $request, $id = null)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid. Silakan login kembali.'
            ], 401);
        }

        // Tentukan ID pasien: dari parameter URL ($id), query/body (patient_id), atau profil user
        $targetPatientId = $id ?: ($request->input('patient_id') ?: null);

        $patient = null;
        if ($targetPatientId) {
            $patient = Patient::find($targetPatientId);
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pasien tidak ditemukan.'
                ], 404);
            }

            if (!$patient->isAccessibleBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
                ], 403);
            }
        } else {
            if ($user->user_type_id == 2) {
                $patient = Patient::where('user_id', $user->id)->first();
            }
        }

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Profil pasien tidak ditemukan. Pastikan akun terdaftar sebagai pasien.'
            ], 404);
        }

        // Eager load relasi penting
        $patient->loadMissing([
            'user:id,name,phone,gender,photo',
            'puskesmas:id,name'
        ]);

        $puskesmasName = optional($patient->puskesmas)->name ?? 'Puskesmas';

        // 1. Info Pasien
        $patientData = [
            'id'                   => $patient->id,
            'user_id'              => $patient->user_id,
            'name'                 => optional($patient->user)->name ?? 'Pasien TB',
            'nik'                  => $patient->nik,
            'phone'                => optional($patient->user)->phone,
            'gender'               => optional($patient->user)->gender,
            'photo'                => optional($patient->user)->photo ? asset('images/' . $patient->user->photo) : null,
            'puskesmas_id'         => $patient->puskesmas_id,
            'puskesmas_name'       => $puskesmasName,
            'treatment_start_date' => $patient->treatment_start_date ? Carbon::parse($patient->treatment_start_date)->format('Y-m-d') : null,
        ];

        // 2. Info Pengobatan Aktif (Treatment)
        $treatment = PatientTreatment::with('treatmentType:id,treatment_type')
            ->where('patient_id', $patient->id)
            ->orderByDesc('start_date')
            ->first();

        $treatmentData = null;
        $activeTreatmentId = null;
        if ($treatment) {
            $activeTreatmentId = $treatment->id;
            $startDate = $treatment->start_date ? Carbon::parse($treatment->start_date) : null;
            $endDate = $treatment->end_date ? Carbon::parse($treatment->end_date) : null;
            $totalDays = $treatment->treatment_days ?: ($startDate && $endDate ? $startDate->diffInDays($endDate) + 1 : 180);

            $now = Carbon::today();
            $currentDay = 0;
            if ($startDate) {
                if ($now->greaterThanOrEqualTo($startDate)) {
                    $currentDay = $startDate->diffInDays($now) + 1;
                    if ($currentDay > $totalDays) {
                        $currentDay = $totalDays;
                    }
                }
            }

            $progressPercent = $totalDays > 0 ? (int) round(($currentDay / $totalDays) * 100) : 0;
            if ($progressPercent > 100) $progressPercent = 100;

            // Resolve reminder time
            $schedule = $this->resolveMedicationSchedule($patient);
            $reminderTime = $schedule['reminder_time'] ?? $treatment->medication_time;
            if ($reminderTime && strlen($reminderTime) >= 5) {
                $reminderTime = substr($reminderTime, 0, 5);
            }

            $treatmentData = [
                'id'                  => $treatment->id,
                'treatment_type_id'   => $treatment->treatment_type_id,
                'treatment_type_name' => optional($treatment->treatmentType)->treatment_type ?? 'TB Sensitif Obat',
                'treatment_status'    => $treatment->treatment_status ?? 'Berjalan',
                'start_date'          => $treatment->start_date ? Carbon::parse($treatment->start_date)->format('Y-m-d') : null,
                'end_date'            => $treatment->end_date ? Carbon::parse($treatment->end_date)->format('Y-m-d') : null,
                'treatment_days'      => $treatment->treatment_days,
                'current_day'         => $currentDay,
                'total_days'          => $totalDays,
                'progress_percent'    => $progressPercent,
                'medication_time'     => $reminderTime,
            ];
        }

        // 3. Jadwal Kunjungan Terdekat (Next Visit) & Upcoming Visits
        $nextVisitData = null;
        $upcomingVisits = [];
        $visitsQuery = TreatmentVisit::whereHas('patientTreatment', function ($q) use ($patient) {
                $q->where('patient_id', $patient->id);
            })
            ->where('visit_status', '!=', 'Batal')
            ->whereDate('visit_date', '>=', Carbon::today())
            ->orderBy('visit_date', 'asc')
            ->orderBy('visit_time', 'asc');

        $nextVisit = (clone $visitsQuery)->first();
        if ($nextVisit) {
            $nextVisitData = [
                'id'             => $nextVisit->id,
                'visit_date'     => $nextVisit->visit_date ? Carbon::parse($nextVisit->visit_date)->format('Y-m-d') : null,
                'visit_time'     => $nextVisit->visit_time ? substr($nextVisit->visit_time, 0, 5) : null,
                'visit_status'   => $nextVisit->visit_status,
                'notes'          => $nextVisit->notes,
                'puskesmas_name' => $puskesmasName,
            ];
        }

        $upcomingVisits = $visitsQuery->take(3)->get()->map(function ($v) use ($puskesmasName) {
            return [
                'id'             => $v->id,
                'visit_date'     => $v->visit_date ? Carbon::parse($v->visit_date)->format('Y-m-d') : null,
                'visit_time'     => $v->visit_time ? substr($v->visit_time, 0, 5) : null,
                'visit_status'   => $v->visit_status,
                'notes'          => $v->notes,
                'puskesmas_name' => $puskesmasName,
            ];
        });

        // 4. Status Minum Obat Hari Ini (Medication)
        $isTakenToday = false;
        $todayRecordData = null;
        $todayRecord = MedicationRecord::whereHas('patientTreatment', function ($q) use ($patient) {
                $q->where('patient_id', $patient->id);
            })
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

        if ($todayRecord) {
            $isTakenToday = true;
            $todayRecordData = [
                'id'           => $todayRecord->id,
                'is_verified'  => (bool) $todayRecord->is_verified,
                'submitted_at' => $todayRecord->created_at ? $todayRecord->created_at->format('Y-m-d H:i:s') : null,
                'photo'        => $todayRecord->photo ? asset('images/' . $todayRecord->photo) : null,
            ];
        }

        $medicationData = [
            'reminder_time'  => $treatmentData['medication_time'] ?? null,
            'is_taken_today' => $isTakenToday,
            'today_record'   => $todayRecordData,
        ];

        // 5. Ringkasan Konsultasi Terakhir
        $consultation = Consultation::with(['recipient:id,name', 'replies.user:id,name'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
            })
            ->latest('updated_at')
            ->first();

        $consultationData = null;
        if ($consultation) {
            $doctorOrOfficer = optional($consultation->recipient)->name;
            if (!$doctorOrOfficer && $consultation->replies->isNotEmpty()) {
                $doctorOrOfficer = optional($consultation->replies->last()->user)->name;
            }

            $consultationData = [
                'id'                     => $consultation->id,
                'title'                  => $consultation->title,
                'latest_message'         => $consultation->replies->isNotEmpty()
                    ? $consultation->replies->last()->message
                    : $consultation->message,
                'doctor_or_officer_name' => $doctorOrOfficer ?? 'Petugas TB Care',
                'is_answered'            => (bool) $consultation->is_answered,
                'updated_at'             => $consultation->updated_at ? $consultation->updated_at->format('Y-m-d H:i') : null,
                'unread_replies_count'   => $consultation->replies->where('is_read', false)->where('user_id', '!=', $user->id)->count(),
            ];
        }

        // 6. Notifikasi Sistem Terbaru
        $notifications = SystemNotification::where(function ($q) use ($patient) {
                $q->whereIn('target_role', ['Pasien', 'Semua']);
                if ($patient->puskesmas_id) {
                    $q->where(function ($sq) use ($patient) {
                        $sq->whereNull('target_puskesmas_id')
                           ->orWhere('target_puskesmas_id', $patient->puskesmas_id);
                    });
                }
            })
            ->where('status', 'Terkirim')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'message'    => $n->message,
                    'type'       => $n->type,
                    'created_at' => $n->created_at ? $n->created_at->format('Y-m-d H:i') : null,
                ];
            });

        // 7. Materi Edukasi Terbaru
        $education = EducationalMaterial::where('is_publish', 1)
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($e) {
                return [
                    'id'             => $e->id,
                    'title_material' => $e->title_material,
                    'description'    => $e->description,
                    'material_type'  => $e->material_type,
                    'photo'          => $e->material_type === 'image' ? ($e->image_path ?: null) : null,
                    'video_url'      => $e->material_type === 'video' ? $e->video_url : null,
                    'created_at'     => $e->created_at ? $e->created_at->format('Y-m-d H:i') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data beranda berhasil dimuat.',
            'data'    => [
                'patient'                    => $patientData,
                'treatment'                  => $treatmentData,
                'next_visit'                 => $nextVisitData,
                'upcoming_visits'            => $upcomingVisits,
                'medication'                 => $medicationData,
                'consultation'               => $consultationData,
                'notifications'              => $notifications,
                'unread_notifications_count' => $notifications->count(),
                'education'                  => $education,
            ]
        ], 200);
    }

    /**
     * Normalisasi resep obat (JSON string, array, atau string berpemisah newline/koma)
     * menjadi format list obat yang terstruktur dan bersih.
     *
     * @param mixed $rawPrescription
     * @param string|null $defaultMedicationTime
     * @return array
     */
    protected function normalizePrescription($rawPrescription, $defaultMedicationTime = null): array
    {
        if (empty($rawPrescription)) {
            return [];
        }

        $items = [];

        if (is_string($rawPrescription)) {
            $decoded = json_decode($rawPrescription, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            } else {
                $lines = preg_split('/[\r\n,]+/', $rawPrescription);
                $items = array_filter(array_map('trim', $lines));
            }
        } elseif (is_array($rawPrescription)) {
            $items = $rawPrescription;
        }

        $formattedMedications = [];

        foreach ($items as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            if (is_string($item)) {
                $splitParts = preg_split('/[\r\n,]+/', $item);
                foreach ($splitParts as $part) {
                    $cleaned = trim($part);
                    if ($cleaned !== '' && $cleaned !== '-') {
                        $formattedMedications[] = [
                            'name'          => $cleaned,
                            'dosage'        => null,
                            'frequency'     => null,
                            'rules'         => null,
                            'schedule_time' => $defaultMedicationTime ? substr($defaultMedicationTime, 0, 5) : null,
                        ];
                    }
                }
            } elseif (is_array($item)) {
                $rawName = $item['name'] ?? $item['nama_obat'] ?? $item['nama'] ?? $item['drug_name'] ?? $item['medicine_name'] ?? '-';
                $dosage = $item['dosage'] ?? $item['dosis'] ?? null;
                $frequency = $item['frequency'] ?? $item['frekuensi'] ?? $item['aturan_pakai'] ?? null;
                $rules = $item['rules'] ?? $item['instruksi'] ?? $item['aturan'] ?? $item['catatan'] ?? null;
                $time = $item['schedule_time'] ?? $item['time'] ?? $item['jadwal'] ?? ($defaultMedicationTime ? substr($defaultMedicationTime, 0, 5) : null);

                if (is_string($rawName) && (strpos($rawName, "\n") !== false || strpos($rawName, "\r") !== false)) {
                    $splitNames = preg_split('/[\r\n]+/', $rawName);
                    foreach ($splitNames as $subName) {
                        $cleanSub = trim($subName);
                        if ($cleanSub !== '' && $cleanSub !== '-') {
                            $formattedMedications[] = [
                                'name'          => $cleanSub,
                                'dosage'        => $dosage,
                                'frequency'     => $frequency,
                                'rules'         => $rules,
                                'schedule_time' => $time,
                            ];
                        }
                    }
                } else {
                    $cleanName = trim((string)$rawName);
                    if ($cleanName !== '' && $cleanName !== '-') {
                        $formattedMedications[] = [
                            'name'          => $cleanName,
                            'dosage'        => $dosage,
                            'frequency'     => $frequency,
                            'rules'         => $rules,
                            'schedule_time' => $time,
                        ];
                    }
                }
            }
        }

        return $formattedMedications;
    }

    /**
     * Mengambil daftar nama obat bersih sebagai array string.
     *
     * @param mixed $rawPrescription
     * @return array|null
     */
    protected function normalizePrescriptionNames($rawPrescription): ?array
    {
        $meds = $this->normalizePrescription($rawPrescription);
        if (empty($meds)) {
            return null;
        }

        return array_map(function ($med) {
            return $med['name'];
        }, $meds);
    }
}

