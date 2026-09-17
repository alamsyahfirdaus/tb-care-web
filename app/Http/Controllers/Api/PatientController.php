<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\User;
use App\Models\Village;
use App\Policies\PatientPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

                'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                    ? "$subdistrictName, $districtName, $provinceName"
                    : null,

                'name'           => $patient->user->name,
                'email'          => $patient->user->email,
                'phone'          => $patient->user->phone,
                'gender'         => $patient->user->gender,
                'place_of_birth' => $patient->user->place_of_birth,
                'date_of_birth'  => $patient->user->date_of_birth,

                'puskesmas'      => optional($patient->puskesmas)->name,

                'treatments'     => $patient->treatments->map(function ($treatment) {
                    return [
                        'id'                => $treatment->id,
                        'treatment_type_id' => $treatment->treatment_type_id,
                        'treatment_status'  => $treatment->treatment_status,
                        'diagnosis_date'    => $treatment->diagnosis_date,
                        'start_date'        => $treatment->start_date,
                        'end_date'          => $treatment->end_date,
                        'treatment_days'    => $treatment->treatment_days,
                        'medication_time'   => $treatment->medication_time,

                        'visits' => $treatment->visits->map(function ($visit) {
                            return [
                                'id'           => $visit->id,
                                'visit_date'   => $visit->visit_date,
                                'visit_time'   => $visit->visit_time,
                                'visit_status' => $visit->visit_status,
                                'notes'        => $visit->notes,
                            ];
                        }),
                        'prescription'     => $treatment->prescription ? json_decode($treatment->prescription, true) : null,
                    ];
                }),
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

        // 3. Verifikasi Wilayah Wewenang untuk Kader
        if ($user->user_type_id == 3 && $user->officer && $user->officer->isKader()) {
            // Jangan mempercayai puskesmas_id dari client; gunakan puskesmas milik officer
            $validated['puskesmas_id'] = $user->officer->puskesmas_id;

            $policy = app(PatientPolicy::class);
            $hasAuthority = $policy->canAssignTerritory(
                $user,
                $validated['village_id'] ?? null,
                $validated['rw'] ?? null,
                $validated['rt'] ?? null,
                $validated['puskesmas_id']
            );

            if (!$hasAuthority) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang menggunakan wilayah tersebut.'
                ], 403);
            }
        }

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
                            $q->orderByDesc('visit_date');
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

            'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                ? "$subdistrictName, $districtName, $provinceName"
                : null,

            'name'           => $patient->user->name,
            'email'          => $patient->user->email,
            'phone'          => $patient->user->phone,
            'gender'         => $patient->user->gender,
            'place_of_birth' => $patient->user->place_of_birth,
            'date_of_birth'  => $patient->user->date_of_birth,

            'puskesmas'      => optional($patient->puskesmas)->name,

            'treatments'     => $patient->treatments->map(function ($treatment) {
                return [
                    'id'                => $treatment->id,
                    'treatment_type_id' => $treatment->treatment_type_id,
                    'treatment_status'  => $treatment->treatment_status,
                    'diagnosis_date'    => $treatment->diagnosis_date,
                    'start_date'        => $treatment->start_date,
                    'end_date'          => $treatment->end_date,
                    'treatment_days'    => $treatment->treatment_days,
                    'medication_time'   => $treatment->medication_time,

                    'visits' => $treatment->visits->map(function ($visit) {
                        return [
                            'id'           => $visit->id,
                            'visit_date'   => $visit->visit_date,
                            'visit_time'   => $visit->visit_time,
                            'visit_status' => $visit->visit_status,
                            'notes'        => $visit->notes,
                        ];
                    }),
                    'prescription'     => $treatment->prescription ? json_decode($treatment->prescription, true) : null,
                ];
            }),
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
            return [
                'treatment_type'   => optional($treatment->treatmentType)->treatment_type,
                'start_date'       => $treatment->start_date,
                'end_date'         => $treatment->end_date,
                'treatment_days'   => $treatment->treatment_days,
                'medication_time'  => $treatment->medication_time,
                'treatment_status' => $treatment->treatment_status,
                'prescription'     => $treatment->prescription ? json_decode($treatment->prescription, true) : null,
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
}
