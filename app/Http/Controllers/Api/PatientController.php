<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /* public function indexOld(Request $request)
    {
        // Ambil filter status pengobatan (opsional)
        $treatmentStatus = $request->post('treatment_status');

        // Ambil data user yang sedang login
        $user = Auth::user();

        // Inisialisasi query pasien dengan relasi user, puskesmas, dan subdistrict
        $patientsQuery = Patient::with([
            'user',         // Relasi ke tabel users
            'puskesmas',    // Relasi ke puskesmas tempat pasien terdaftar
            'subdistrict',  // Relasi ke kecamatan pasien
        ]);

        // Tambahkan relasi treatment (pengobatan) dan hanya ambil yang terbaru + 1 kunjungan terakhir
        $patientsQuery->with([
            'treatments' => function ($query) use ($treatmentStatus) {
                $query->when($treatmentStatus, fn($q) => $q->where('treatment_status', $treatmentStatus))
                    ->latest() // Urutkan treatment terbaru
                    ->limit(1) // Ambil satu treatment terakhir
                    ->with(['visits' => fn($q) => $q->latest()->limit(1)]); // Kunjungan terakhir
            }
        ]);

        // Filtering berdasarkan peran pengguna
        if ($user->user_type_id == 1) {
            // Admin: ambil semua pasien
            $patients = $patientsQuery->get();
        } elseif ($user->user_type_id == 3) {
            // Petugas: ambil data berdasarkan wilayah kerja
            $officer = Officer::where('user_id', $user->id)->first();

            if (!$officer) {
                return response()->json(['message' => 'Data petugas tidak ditemukan.'], 404);
            }

            // Petugas puskesmas (tipe 3 atau 4): ambil pasien di puskesmas yang sama
            if (in_array($officer->officer_type_id, [3, 4])) {
                $patients = $patientsQuery
                    ->where('puskesmas_id', $officer->puskesmas_id)
                    ->get();
            } else {
                // Petugas kabupaten/kota: ambil pasien dari seluruh puskesmas dalam 1 kabupaten
                $patients = $patientsQuery
                    ->whereHas(
                        'puskesmas',
                        fn($q) =>
                        $q->where('district_id', $officer->district_id)
                    )
                    ->get();
            }
        } else {
            // User tanpa hak akses
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat data pasien.'
            ], 403);
        }

        // Mapping data pasien menjadi array JSON yang lebih informatif
        $patientsData = $patients->map(function ($patient) {
            // Ambil informasi lokasi lengkap jika tersedia
            $subdistrictName = optional($patient->subdistrict)->name;
            $districtName    = optional($patient->subdistrict?->district)->name;
            $provinceName    = optional($patient->subdistrict?->district?->province)->name;

            $treatment = $patient->treatments->first();
            $visit     = $treatment?->visits->first();

            return [
                // Data pasien
                'id'              => $patient->id,
                'user_id'         => $patient->user_id,
                'nik'             => $patient->nik,
                'address'         => $patient->address,
                'puskesmas_id'    => $patient->puskesmas_id,
                'subdistrict_id'  => $patient->subdistrict_id,
                // Lokasi lengkap jika tersedia
                'subdistrict'     => ($subdistrictName && $districtName && $provinceName)
                    ? "$subdistrictName, $districtName, $provinceName"
                    : null,

                // Informasi akun user terkait pasien

                'name'            => $patient->user->name,
                'email'           => $patient->user->email,
                'phone'           => $patient->user->phone,
                'gender'          => $patient->user->gender,
                'place_of_birth'  => $patient->user->place_of_birth,
                'date_of_birth'   => $patient->user->date_of_birth,

                // Informasi puskesmas
                'puskesmas'       => optional($patient->puskesmas)->name,

                // Data pengobatan terakhir
                'patient_treatment_id'      => $treatment?->id ?? null,
                'patient_treatment_type_id' => $treatment?->treatment_type_id ?? null,
                'treatment_status'          => $treatment?->treatment_status ?? 'Belum Mulai',
                'diagnosis_date'            => $treatment?->diagnosis_date,
                'start_date'                => $treatment?->start_date,
                'end_date'                  => $treatment?->end_date,

                // Kunjungan terakhir (jika ada)
                'visit_id'       => $visit?->id,
                'visit_date'     => $visit?->visit_date,
                'visit_time'     => $visit?->visit_time,
                'visit_status'   => $visit?->visit_status,
                'notes'          => $visit?->notes,
            ];
        });

        // Kembalikan respons JSON
        return response()->json([
            'message' => 'Data pasien berhasil diambil.',
            'data'    => $patientsData
        ]);
    } */

    public function index(Request $request)
    {
        // Ambil filter status pengobatan dari request (jika ada)
        $treatmentStatus = $request->post('treatment_status');

        // Ambil data user yang sedang login
        $user = Auth::user();

        // Siapkan query untuk mengambil data pasien beserta relasi-relasinya
        $patientsQuery = Patient::with([
            'user',         // Relasi ke tabel users
            'puskesmas',    // Relasi ke puskesmas tempat pasien terdaftar
            'subdistrict',  // Relasi ke kecamatan pasien

            // Relasi ke data pengobatan (treatments)
            'treatments' => function ($query) use ($treatmentStatus) {
                // Filter treatment jika treatment_status diberikan
                $query->when($treatmentStatus, function ($q) use ($treatmentStatus) {
                    $q->where('treatment_status', $treatmentStatus);
                })
                    ->orderByDesc('start_date') // Urutkan treatment dari yang terbaru
                    ->with([
                        // Ambil semua data kunjungan (visits) per treatment, diurutkan dari yang terbaru
                        'visits' => function ($q) {
                            $q->orderByDesc('visit_date');
                        }
                    ]);
            }
        ]);

        // Filter akses berdasarkan peran user
        if ($user->user_type_id == 1) {
            // Jika admin, ambil semua data pasien
            $patients = $patientsQuery->get();
        } elseif ($user->user_type_id == 3) {
            // Jika petugas, ambil data berdasarkan wilayah kerjanya
            $officer = Officer::where('user_id', $user->id)->first();

            // Jika data petugas tidak ditemukan
            if (!$officer) {
                return response()->json(['message' => 'Data petugas tidak ditemukan.'], 404);
            }

            // Jika petugas puskesmas (tipe 3 atau 4), ambil pasien di puskesmas yang sama
            if (in_array($officer->officer_type_id, [3, 4])) {
                $patients = $patientsQuery
                    ->where('puskesmas_id', $officer->puskesmas_id)
                    ->get();
            } else {
                // Jika petugas kabupaten/kota, ambil pasien dari semua puskesmas di kabupaten yang sama
                $patients = $patientsQuery
                    ->whereHas('puskesmas', function ($q) use ($officer) {
                        $q->where('district_id', $officer->district_id);
                    })
                    ->get();
            }
        } else {
            // User tidak memiliki akses
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat data pasien.'
            ], 403);
        }

        // Mapping data pasien menjadi format array JSON
        $patientsData = $patients->map(function ($patient) {
            // Ambil nama lokasi secara berjenjang
            $subdistrictName = optional($patient->subdistrict)->name;
            $districtName    = optional($patient->subdistrict?->district)->name;
            $provinceName    = optional($patient->subdistrict?->district?->province)->name;

            return [
                // Data dasar pasien
                'id'             => $patient->id,
                'user_id'        => $patient->user_id,
                'nik'            => $patient->nik,
                'address'        => $patient->address,
                'puskesmas_id'   => $patient->puskesmas_id,
                'subdistrict_id' => $patient->subdistrict_id,

                // Nama lengkap lokasi (kecamatan, kabupaten, provinsi)
                'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                    ? "$subdistrictName, $districtName, $provinceName"
                    : null,

                // Data user terkait pasien
                'name'           => $patient->user->name,
                'email'          => $patient->user->email,
                'phone'          => $patient->user->phone,
                'gender'         => $patient->user->gender,
                'place_of_birth' => $patient->user->place_of_birth,
                'date_of_birth'  => $patient->user->date_of_birth,

                // Nama puskesmas
                'puskesmas'      => optional($patient->puskesmas)->name,

                // Daftar seluruh treatment beserta visit masing-masing
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

                        // Daftar semua kunjungan pada treatment ini
                        'visits' => $treatment->visits->map(function ($visit) {
                            return [
                                'id'           => $visit->id,
                                'visit_date'   => $visit->visit_date,
                                'visit_time'   => $visit->visit_time,
                                'visit_status' => $visit->visit_status,
                                'notes'        => $visit->notes,
                            ];
                        }),
                    ];
                }),
            ];
        });

        // Kembalikan response JSON
        return response()->json([
            'message' => 'Data pasien berhasil diambil.',
            'data'    => $patientsData
        ]);
    }

    public function store(Request $request)
    {
        // 1. Ambil data pasien dan user yang terkait (jika ada)
        $existingPatient = Patient::with('user')->find($request->patient_id);
        $existingUser = $existingPatient?->user;

        // 2. Validasi input
        $validated = $request->validate([
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('patients', 'nik')->ignore($existingPatient ? $existingPatient->id : null),
            ],
            'name'             => 'required|string|max:255',
            'email'            => ['required', 'email', Rule::unique('users', 'email')->ignore($existingUser ? $existingUser->id : null)],
            'phone'            => ['required', 'string', 'min:10', 'max:15', Rule::unique('users', 'phone')->ignore($existingUser ? $existingUser->id : null)],
            'gender'           => 'required|in:L,P',
            'place_of_birth'   => 'required|string|max:100',
            'date_of_birth'    => 'required|date',
            'puskesmas_id'     => 'required|exists:puskesmas,id',
            'subdistrict_id'   => 'required|exists:subdistricts,id',
            'address'          => 'nullable|string',
            'occupation'       => 'nullable|string',
            'height'           => 'nullable|integer',
            'weight'           => 'nullable|integer',
            'blood_type'       => 'nullable|string|max:3',
            'diagnosis_date'   => 'nullable|date',
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
            'place_of_birth.required' => 'Tempat lahir wajib diisi.',
            'place_of_birth.max'      => 'Tempat lahir maksimal 100 karakter.',
            'date_of_birth.required'  => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date'      => 'Format tanggal lahir tidak valid.',
            'puskesmas_id.required'   => 'Puskesmas wajib dipilih.',
            'puskesmas_id.exists'     => 'Puskesmas tidak ditemukan.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
            'subdistrict_id.exists'   => 'Kecamatan tidak ditemukan.',
            'blood_type.max'          => 'Golongan darah maksimal 3 karakter.',
        ]);

        // 3. Jika user belum ada, buat user baru
        if (!$existingUser) {
            $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $validated['email'])[0]);
            $username = $baseUsername;
            $counter = 1;

            // Hindari duplikasi username
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter++;
            }

            $existingUser = new User([
                'username' => $username,
                'password' => Hash::make($username), // Password default = username
            ]);
        }

        // 4. Simpan atau perbarui data user
        $existingUser->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'],
            'gender'         => $validated['gender'],
            'place_of_birth' => $validated['place_of_birth'],
            'date_of_birth'  => $validated['date_of_birth'],
            'user_type_id'   => 2, // 2 = Pasien
            'is_active'      => true,
        ]);
        $existingUser->save();

        // 5. Jika pasien belum ada, buat data pasien baru
        if (!$existingPatient) {
            $existingPatient = new Patient([
                'user_id' => $existingUser->id,
            ]);
        }

        // 6. Simpan atau perbarui data pasien
        $existingPatient->fill([
            'nik'             => $validated['nik'],
            'address'         => $validated['address'] ?? null,
            'occupation'      => $validated['occupation'] ?? null,
            'height'          => $validated['height'] ?? null,
            'weight'          => $validated['weight'] ?? null,
            'blood_type'      => $validated['blood_type'] ?? null,
            'diagnosis_date'  => $validated['diagnosis_date'] ?? null,
            'subdistrict_id'  => $validated['subdistrict_id'],
            'puskesmas_id'    => $validated['puskesmas_id'],
        ]);
        $existingPatient->save();

        // 7. Tentukan pesan respons
        $message = $request->patient_id
            ? 'Data pasien berhasil diperbarui.'
            : 'Data pasien baru berhasil ditambahkan.';

        // 8. Kembalikan respons JSON
        return response()->json([
            'message' => $message,
            'data'    => $existingPatient->load('user'),
        ]);
    }


    /* public function showOld($id)
    {
        // Ambil data pasien beserta relasi terkait
        $patient = Patient::with([
            'user',
            'puskesmas',
            'subdistrict.district.province',
            'treatments' => fn($q) => $q->latest()->limit(1)->with([
                'visits' => fn($q) => $q->latest()->limit(1) // ambil kunjungan terakhir
            ])
        ])->find($id);

        // Jika pasien tidak ditemukan
        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Ambil nama wilayah
        $subdistrictName = optional($patient->subdistrict)->name;
        $districtName    = optional($patient->subdistrict?->district)->name;
        $provinceName    = optional($patient->subdistrict?->district?->province)->name;

        // Ambil pengobatan terakhir dan kunjungan terakhir
        $lastTreatment = $patient->treatments->first();
        $lastVisit     = $lastTreatment?->visits->first();

        // Susun data pasien lengkap
        $patientData = [
            // Data pasien
            'id'             => $patient->id,
            'user_id'        => $patient->user_id,
            'nik'            => $patient->nik,
            'address'        => $patient->address,
            'puskesmas_id'   => $patient->puskesmas_id,
            'subdistrict_id' => $patient->subdistrict_id,

            // Alamat lengkap (jika tersedia)
            'subdistrict' => ($subdistrictName && $districtName && $provinceName)
                ? $subdistrictName . ', ' . $districtName . ', ' . $provinceName
                : null,

            // Data user
            'name'           => $patient->user->name,
            'email'          => $patient->user->email,
            'phone'          => $patient->user->phone,
            'gender'         => $patient->user->gender,
            'place_of_birth' => $patient->user->place_of_birth,
            'date_of_birth'  => $patient->user->date_of_birth,

            // Nama puskesmas
            'puskesmas'      => optional($patient->puskesmas)->name,

            // Pengobatan terakhir
            'patient_treatment_id' => $lastTreatment?->id ?? null,
            'patient_treatment_type_id' => $lastTreatment?->patient_treatment_id ?? null,
            'treatment_status'     => $lastTreatment?->treatment_status ?? 'Belum Mulai',
            'diagnosis_date'           => $lastTreatment?->diagnosis_date,
            'start_date'           => $lastTreatment?->start_date,
            'end_date'             => $lastTreatment?->end_date,

            // Kunjungan terakhir
            'visit_id'       => $lastVisit?->id,
            'visit_date'     => $lastVisit?->visit_date,
            'visit_time'     => $lastVisit?->visit_time,
            'visit_status'   => $lastVisit?->visit_status,
            'notes'          => $lastVisit?->notes,
        ];

        // Response sukses
        return response()->json([
            'message' => 'Detail data pasien berhasil diambil.',
            'data'    => $patientData
        ]);
    } */

    public function show($id)
    {
        // Ambil data pasien berdasarkan ID beserta relasi terkait
        $patient = Patient::with([
            'user',
            'puskesmas',
            'subdistrict.district.province',
            'treatments' => function ($query) {
                $query->orderByDesc('start_date') // Urutkan treatment dari terbaru
                    ->with([
                        'visits' => function ($q) {
                            $q->orderByDesc('visit_date'); // Urutkan visit dari terbaru
                        }
                    ]);
            }
        ])->find($id);

        // Jika data pasien tidak ditemukan
        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Ambil nama wilayah berjenjang
        $subdistrictName = optional($patient->subdistrict)->name;
        $districtName    = optional($patient->subdistrict?->district)->name;
        $provinceName    = optional($patient->subdistrict?->district?->province)->name;

        // Susun data lengkap pasien dalam format array
        $patientData = [
            // Informasi dasar pasien
            'id'             => $patient->id,
            'user_id'        => $patient->user_id,
            'nik'            => $patient->nik,
            'address'        => $patient->address,
            'puskesmas_id'   => $patient->puskesmas_id,
            'subdistrict_id' => $patient->subdistrict_id,

            // Alamat lengkap (jika tersedia)
            'subdistrict'    => ($subdistrictName && $districtName && $provinceName)
                ? "$subdistrictName, $districtName, $provinceName"
                : null,

            // Informasi user
            'name'           => $patient->user->name,
            'email'          => $patient->user->email,
            'phone'          => $patient->user->phone,
            'gender'         => $patient->user->gender,
            'place_of_birth' => $patient->user->place_of_birth,
            'date_of_birth'  => $patient->user->date_of_birth,

            // Nama puskesmas
            'puskesmas'      => optional($patient->puskesmas)->name,

            // Daftar seluruh treatment dan seluruh visit-nya
            'treatments'     => $patient->treatments->map(function ($treatment) {
                return [
                    'id'                => $treatment->id,
                    'treatment_type_id' => $treatment->treatment_type_id,
                    'treatment_status'  => $treatment->treatment_status,
                    'diagnosis_date'    => $treatment->diagnosis_date,
                    'start_date'        => $treatment->start_date,
                    'end_date'          => $treatment->end_date,

                    // Kumpulan kunjungan dalam treatment ini
                    'visits' => $treatment->visits->map(function ($visit) {
                        return [
                            'id'           => $visit->id,
                            'visit_date'   => $visit->visit_date,
                            'visit_time'   => $visit->visit_time,
                            'visit_status' => $visit->visit_status,
                            'notes'        => $visit->notes,
                        ];
                    }),
                ];
            }),
        ];

        // Kembalikan data dalam bentuk response JSON
        return response()->json([
            'message' => 'Detail data pasien berhasil diambil.',
            'data'    => $patientData
        ]);
    }

    public function destroy($id)
    {
        // Cari data pasien berdasarkan ID
        $patient = Patient::with('user')->find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Ambil user terkait
        $user = $patient->user;

        // Hapus gambar jika ada
        if ($user && $user->photo) {
            $filePath = 'images/' . $user->photo;
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        // Hapus user (otomatis hapus pasien jika ada foreign key cascade)
        if ($user) {
            $user->delete();
        } else {
            $patient->delete(); // fallback jika user tidak ditemukan
        }

        return response()->json([
            'message' => 'Data pasien berhasil dihapus.'
        ]);
    }

    public function treatmentHistory($id)
    {
        // Cari data pasien
        $patient = Patient::find($id);

        // Jika pasien tidak ditemukan
        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Ambil semua data pengobatan pasien, urutkan dari terbaru
        $treatments = PatientTreatment::with('treatmentType')
            ->where('patient_id', $id)
            ->orderByDesc('start_date')
            ->get();

        // Format data pengobatan
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

        // Kembalikan data dalam format JSON
        return response()->json([
            'message' => 'Riwayat pengobatan berhasil diambil.',
            'data'    => $treatmentHistory
        ]);
    }
}
