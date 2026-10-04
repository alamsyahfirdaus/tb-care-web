<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\District;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentType;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input: bisa pakai username atau email
        $request->validate([
            'username' => 'required|string', // Bisa email atau username
            'password' => 'required|string',
        ], [
            'username.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Cari user berdasarkan username atau email
        $user = User::where('email', $request->username)
            ->orWhere('username', $request->username)
            ->first();

        // Cek apakah user ditemukan dan password cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Login gagal. Username/email atau password salah.'
            ], 401); // Unauthorized
        }

        // Cek apakah akun aktif
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda belum aktif. Silakan hubungi administrator.'
            ], 403); // Forbidden
        }

        // Hapus token lama (opsional)
        // $user->tokens()->delete();

        // Simpan waktu terakhir login
        $user->last_login_at = now();
        $user->save();

        if ($user->user_type_id == 2) {
            // Jika user adalah pasien, ambil data pasien
            $patient = Patient::where('user_id', $user->id)->first();
            $user->patient = $patient;
        }

        // Buat token baru
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Ambil token aktif dari user yang sedang login
        $user = $request->user();
        $token = $user?->currentAccessToken();

        // Jika token tidak ditemukan (belum login atau token tidak valid)
        if (!$token) {
            return response()->json([
                'message' => 'Tidak ada token yang ditemukan atau pengguna belum login.'
            ], 401); // Unauthorized
        }

        // Hapus token FCM saat logout
        if ($user) {
            $user->fcm_token = null;
            $user->save();
        }

        // Hapus token aktif untuk logout
        $token->delete();

        // Kembalikan response logout berhasil
        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    public function register(Request $request)
    {
        // Berdasarkan hasil FGD, registrasi publik mobile HANYA untuk Pasien.
        // Client tidak boleh menentukan role/privilege.
        return $this->registerPatient($request);
    }

    public function registerPatient(Request $request)
    {
        // Dukung alias nama field jika dikirim dalam bahasa Indonesia
        if ($request->filled('tanggal_mulai_pengobatan') && !$request->filled('treatment_start_date')) {
            $request->merge(['treatment_start_date' => $request->input('tanggal_mulai_pengobatan')]);
        }
        if ($request->filled('regency_id') && !$request->filled('district_id')) {
            $request->merge(['district_id' => $request->input('regency_id')]);
        }

        // Validasi data input dari form
        $validatedData = $request->validate([
            'name'                 => 'required|string|min:3|max:255',
            'nik'                  => 'required|digits:16|unique:patients,nik',
            'phone'                => 'required|string|min:10|max:15|unique:users,phone',
            'puskesmas_id'         => 'required|exists:puskesmas,id',
            'treatment_start_date' => 'required|date|before_or_equal:today',
            'province_id'          => 'nullable|exists:provinces,id',
            'district_id'          => 'nullable|exists:districts,id',
            'subdistrict_id'       => 'required|exists:subdistricts,id',
            'village_id'           => 'required|exists:villages,id',
            'address'              => 'nullable|string|max:500',
            'gender'               => 'nullable|in:L,P',
            'email'                => 'nullable|string|email|unique:users,email',
            'date_of_birth'        => 'nullable|date',
        ], [
            'name.required'                 => 'Nama lengkap wajib diisi.',
            'name.min'                      => 'Nama lengkap minimal 3 karakter.',
            'nik.required'                  => 'NIK wajib diisi.',
            'nik.digits'                    => 'NIK harus terdiri dari 16 digit.',
            'nik.unique'                    => 'NIK sudah terdaftar. Silakan gunakan NIK lain atau login menggunakan akun yang sudah tersedia.',
            'phone.required'                => 'Nomor HP wajib diisi.',
            'phone.min'                     => 'Nomor HP minimal 10 digit.',
            'phone.max'                     => 'Nomor HP maksimal 15 digit.',
            'phone.unique'                  => 'Nomor HP sudah terdaftar.',
            'puskesmas_id.required'         => 'Puskesmas wajib dipilih.',
            'puskesmas_id.exists'           => 'Puskesmas tidak valid.',
            'treatment_start_date.required' => 'Tanggal mulai pengobatan wajib diisi.',
            'treatment_start_date.date'     => 'Format tanggal mulai pengobatan tidak valid.',
            'treatment_start_date.before_or_equal' => 'Tanggal mulai pengobatan tidak boleh di masa depan.',
            'subdistrict_id.required'       => 'Kecamatan wajib dipilih.',
            'subdistrict_id.exists'         => 'Kecamatan tidak valid.',
            'village_id.required'           => 'Desa/Kelurahan wajib dipilih.',
            'village_id.exists'             => 'Desa/Kelurahan tidak valid.',
        ]);

        // Pastikan kolom diagnosis_date di database mengizinkan NULL (antisipasi jika migration belum dijalankan di server)
        // Dilakukan SEBELUM DB::transaction karena perintah DDL (ALTER TABLE) di MySQL memicu implicit commit
        static $ensuredDiagnosisDateNullable = false;
        if (!$ensuredDiagnosisDateNullable) {
            try {
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement('ALTER TABLE patient_treatments MODIFY diagnosis_date DATE NULL');
                }
            } catch (\Throwable $e) {
                // Abaikan jika sudah di-alter atau user database tidak memiliki hak akses DDL ALTER
            }
            $ensuredDiagnosisDateNullable = true;
        }

        return DB::transaction(function () use ($validatedData) {
            // 1. Buat username otomatis unik
            $username = $this->generateUsername($validatedData['name']);

            // 2. Buat password default pasien (123456)
            $plainPassword = '123456';
            $hashedPassword = Hash::make($plainPassword);

            // 3. Simpan data user ke tabel `users` (Role Pasien = 2)
            $user = User::create([
                'name'          => trim($validatedData['name']),
                'email'         => $validatedData['email'] ?? null,
                'username'      => $username,
                'password'      => $hashedPassword,
                'phone'         => trim($validatedData['phone']),
                'gender'        => $validatedData['gender'] ?? null,
                'date_of_birth' => $validatedData['date_of_birth'] ?? null,
                'user_type_id'  => 2, // 2 = Pasien
                'is_active'     => true, // Default aktif
            ]);

            // 4. Susun alamat terstruktur jika belum diisi manual
            $address = $validatedData['address'] ?? null;
            if (empty($address)) {
                $village = Village::find($validatedData['village_id']);
                $subdistrict = Subdistrict::with('district.province')->find($validatedData['subdistrict_id']);
                $villageName = $village ? $village->name : '';
                $subName = $subdistrict ? $subdistrict->name : '';
                $distName = $subdistrict && $subdistrict->district ? $subdistrict->district->name : '';
                $provName = $subdistrict && $subdistrict->district && $subdistrict->district->province ? $subdistrict->district->province->name : '';
                $addressParts = array_filter([$villageName ? "Desa/Kel. $villageName" : '', $subName ? "Kec. $subName" : '', $distName, $provName]);
                $address = implode(', ', $addressParts);
            }

            // 5. Simpan data pasien
            $patient = Patient::create([
                'user_id'              => $user->id,
                'nik'                  => trim($validatedData['nik']),
                'puskesmas_id'         => $validatedData['puskesmas_id'],
                'subdistrict_id'       => $validatedData['subdistrict_id'],
                'village_id'           => $validatedData['village_id'],
                'treatment_start_date' => $validatedData['treatment_start_date'],
                'address'              => $address,
            ]);

            // 6. Buat inisialisasi riwayat pengobatan awal (PatientTreatment) secara otomatis
            $startDate = $validatedData['treatment_start_date'];
            $treatmentType = TreatmentType::find(1);
            $endDate = null;
            $treatmentDays = null;
            if ($treatmentType && $treatmentType->treatment_duration && $treatmentType->duration_unit) {
                $startDateCarbon = Carbon::parse($startDate);
                $endDate = $startDateCarbon->copy()->addMonths($treatmentType->treatment_duration)->toDateString();
                $treatmentDays = $startDateCarbon->diffInDays(Carbon::parse($endDate));
            }

            try {
                $treatment = PatientTreatment::create([
                    'patient_id'        => $patient->id,
                    'treatment_type_id' => 1,
                    'diagnosis_date'    => null,
                    'start_date'        => $startDate,
                    'end_date'          => $endDate,
                    'treatment_days'    => $treatmentDays,
                    'medication_time'   => '07:00:00',
                    'prescription'      => null,
                    'treatment_status'  => 'Berjalan',
                ]);
            } catch (\Illuminate\Database\QueryException $qe) {
                // Jika database server belum menjalankan migration dan kolom diagnosis_date masih NOT NULL,
                // fallback gunakan start_date agar registrasi tidak gagal 500
                if (str_contains($qe->getMessage(), 'diagnosis_date') && (str_contains($qe->getMessage(), 'cannot be null') || str_contains($qe->getMessage(), 'Column \'diagnosis_date\''))) {
                    $treatment = PatientTreatment::create([
                        'patient_id'        => $patient->id,
                        'treatment_type_id' => 1,
                        'diagnosis_date'    => $startDate,
                        'start_date'        => $startDate,
                        'end_date'          => $endDate,
                        'treatment_days'    => $treatmentDays,
                        'medication_time'   => '07:00:00',
                        'prescription'      => null,
                        'treatment_status'  => 'Berjalan',
                    ]);
                } else {
                    throw $qe;
                }
            }

            // 7. Respon JSON saat berhasil (mengembalikan credential untuk ditampilkan sekali ke user)
            return response()->json([
                'success'   => true,
                'message'   => 'Registrasi pasien berhasil.',
                'data'      => [
                    'user_id'   => $user->id,
                    'pasien_id' => $patient->id,
                    'name'      => $user->name,
                    'username'  => $username,
                    'phone'     => $user->phone,
                    'password'  => $plainPassword,
                ],
                'user'      => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $username,
                    'phone'    => $user->phone,
                ],
                'treatment' => [
                    'id'                => $treatment->id,
                    'patient_id'        => $treatment->patient_id,
                    'treatment_type_id' => $treatment->treatment_type_id,
                    'diagnosis_date'    => $treatment->diagnosis_date,
                    'start_date'        => $treatment->start_date,
                    'medication_time'   => $treatment->medication_time,
                    'prescription'      => $treatment->prescription,
                    'treatment_status'  => $treatment->treatment_status,
                ],
            ], 201);
        });
    }

    private function registerOfficer(Request $request)
    {
        $validatedData = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email',
            'phone'           => 'required|string|min:10|max:15',
            'gender'          => 'required|in:L,P',
            'date_of_birth'   => 'nullable|date',
            'officer_type_id' => 'required|in:3,4',
            'puskesmas_id'    => 'required|exists:puskesmas,id',
        ], [
            'name.required'            => 'Nama wajib diisi.',
            'phone.required'           => 'Nomor HP wajib diisi.',
            'phone.unique'             => 'Nomor HP sudah digunakan.',
            'gender.required'          => 'Jenis kelamin wajib dipilih.',
            'officer_type_id.required' => 'Jenis petugas wajib dipilih.',
            'puskesmas_id.required'    => 'Puskesmas wajib dipilih.',
        ]);

       $username = $this->generateUsername($validatedData['name']);

        $user = User::create([
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'] ?? null,
            'username'      => $username,
            'password'      => Hash::make($username),
            'phone'         => $validatedData['phone'],
            'gender'        => $validatedData['gender'],
            'date_of_birth' => $validatedData['date_of_birth'] ?? null,
            'user_type_id'  => 3,
            'is_active'     => true,
        ]);

        Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => $validatedData['officer_type_id'],
            'district_id'     => null,
            'puskesmas_id'    => $validatedData['puskesmas_id'],
        ]);

        return response()->json([
            'message' => 'Registrasi petugas berhasil.',
            'user'    => $user,
            'info'    => 'Username dan password awal Anda adalah: ' . $username,
        ], 201);
    }

    private function generateUsername($name)
    {
        // Ubah menjadi huruf kecil
        $base = strtolower($name);

        // Hilangkan karakter selain huruf, angka, dan spasi
        $base = preg_replace('/[^a-z0-9\s]/', '', $base);

        // Hilangkan spasi
        $base = str_replace(' ', '', $base);

        // Jika hasil kosong
        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        // Pastikan username unik
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    public function getOfficerRoles()
    {
        // Daftar role officer yang tersedia untuk registrasi
        $roles = [
            ['id' => 3, 'name' => 'PJTB'],   // Penanggung Jawab TB Puskesmas
            ['id' => 4, 'name' => 'Kader'],  // Kader Puskesmas
        ];

        // Kirim response JSON ke frontend
        return response()->json([
            'message' => 'Daftar role petugas berhasil diambil.',
            'data'    => $roles
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->user_type_id == 2) {
            $user->load('patient');
        }

        if (in_array($user->user_type_id, [3,4])) {
            $user->load('officer');
        }

        return response()->json([
            'message' => 'Token valid',
            'user' => $user
        ]);
    }

    public function updatePassword()
    {
        User::whereBetween('id', [139, 289])
        ->update([
            'password' => Hash::make('123456'),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ]);
    }
}
