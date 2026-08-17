<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\Officer;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        if ($request->filled('officer_type_id')) {
            return $this->registerOfficer($request);
        }

        return $this->registerPatient($request);
    }

    private function registerPatient(Request $request)
    {
        // Validasi data input dari form
        $validatedData = $request->validate([
            'nik'            => 'nullable|digits:16|unique:patients,nik',
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|string|email|unique:users,email',
            'phone'          => 'required|string|min:10|max:15',
            'gender'         => 'required|in:L,P',
            'date_of_birth'  => 'nullable|date',
            'puskesmas_id'   => 'required|exists:puskesmas,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id', // Tambahan validasi alamat
        ], [
            'nik.required'           => 'NIK wajib diisi.',
            'nik.digits'             => 'NIK harus terdiri dari 16 digit.',
            'nik.unique'             => 'NIK sudah terdaftar.',
            'name.required'          => 'Nama wajib diisi.',
            'email.required'         => 'Email wajib diisi.',
            'email.email'            => 'Format email tidak valid.',
            'email.unique'           => 'Email sudah digunakan.',
            'phone.required'         => 'Nomor HP wajib diisi.',
            'gender.required'        => 'Jenis kelamin wajib dipilih.',
            'puskesmas_id.required'  => 'Puskesmas wajib dipilih.',
            'date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date'     => 'Format tanggal lahir tidak valid.',
            'subdistrict_id.required' => 'Alamat (kecamatan) wajib dipilih.',
            'subdistrict_id.exists'  => 'Kecamatan tidak ditemukan.',
        ]);

        // Buat username otomatis dari email
        $username = $this->generateUsername($validatedData['name']);

        // Hash password awal menggunakan username
        $hashedPassword = Hash::make($username);

        // Simpan data user ke tabel `users`
        $user = User::create([
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'] ?? null,
            'username'      => $username,
            'password'      => $hashedPassword,
            'phone'         => $validatedData['phone'],
            'gender'        => $validatedData['gender'],
            'date_of_birth' => $validatedData['date_of_birth'] ?? null,
            'user_type_id'  => 2, // 2 = Pasien
            'is_active'     => true // Default aktif
        ]);

        Patient::create([
            'user_id'        => $user->id,
            'nik'            => $validatedData['nik'] ?? null,
            'puskesmas_id'   => $validatedData['puskesmas_id'],
            'subdistrict_id' => $validatedData['subdistrict_id'] ?? null,
        ]);

        // Respon JSON saat berhasil
        return response()->json([
            'message' => 'Registrasi pasien berhasil.',
            'user'    => $user,
            'info'    => 'Username dan password awal Anda adalah: ' . $username,
            'note'    => 'Akun Anda masih menunggu verifikasi dari admin sebelum bisa login.'
        ], 201);
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

        // Username dibuat dari nomor HP
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
