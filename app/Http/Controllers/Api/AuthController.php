<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\Officer;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
        $user->tokens()->delete();

        // Simpan waktu terakhir login
        $user->last_login_at = now();
        $user->save();

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
        $token = $request->user()?->currentAccessToken();

        // Jika token tidak ditemukan (belum login atau token tidak valid)
        if (!$token) {
            return response()->json([
                'message' => 'Tidak ada token yang ditemukan atau pengguna belum login.'
            ], 401); // Unauthorized
        }

        // Hapus token aktif untuk logout
        $token->delete();

        // Kembalikan response logout berhasil
        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    // Fungsi untuk registrasi pasien TB
    public function registerPatient(Request $request)
    {
        // Validasi data input dari form
        $validatedData = $request->validate([
            'nik'            => 'required|digits:16|unique:patients,nik',
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|unique:users,email',
            'phone'          => 'required|string|min:10|max:15',
            'gender'         => 'required|in:L,P',
            'date_of_birth'  => 'required|date',
            'puskesmas_id'   => 'required|exists:puskesmas,id',
            'subdistrict_id' => 'required|exists:subdistricts,id', // Tambahan validasi alamat
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
        $username = $this->generateUsername($validatedData['email']);

        // Hash password awal menggunakan username
        $hashedPassword = Hash::make($username);

        // Simpan data user ke tabel `users`
        $user = User::create([
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'],
            'username'      => $username,
            'password'      => $hashedPassword,
            'phone'         => $validatedData['phone'],
            'gender'        => $validatedData['gender'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'user_type_id'  => 2, // 2 = Pasien
            'is_active'     => false // Default tidak aktif, menunggu verifikasi
        ]);

        // Simpan data tambahan ke tabel `patients`
        Patient::create([
            'user_id'        => $user->id,
            'nik'            => $validatedData['nik'],
            'puskesmas_id'   => $validatedData['puskesmas_id'],
            'subdistrict_id' => $validatedData['subdistrict_id'], // Simpan alamat
        ]);

        // Respon JSON saat berhasil
        return response()->json([
            'message' => 'Registrasi pasien berhasil.',
            'user'    => $user,
            'info'    => 'Username dan password awal Anda adalah: ' . $username,
            'note'    => 'Akun Anda masih menunggu verifikasi dari admin sebelum bisa login.'
        ], 201);
    }

    // Fungsi untuk registrasi petugas (PJTB / Kader)
    public function registerOfficer(Request $request)
    {
        // Validasi input form petugas
        $validatedData = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|unique:users,email',
            'phone'             => 'required|string|min:10|max:15',
            'gender'            => 'required|in:L,P',
            'date_of_birth'     => 'required|date', // Tanggal lahir wajib dan valid
            'officer_type_id'   => 'required|in:3,4', // 3 = PJTB, 4 = Kader
            'puskesmas_id'      => 'required|exists:puskesmas,id',
        ], [
            'name.required'             => 'Nama wajib diisi.',
            'email.required'            => 'Email wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'email.unique'              => 'Email sudah digunakan.',
            'phone.required'            => 'Nomor HP wajib diisi.',
            'gender.required'           => 'Jenis kelamin wajib dipilih.',
            'date_of_birth.required'    => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date'        => 'Format tanggal lahir tidak valid.',
            'officer_type_id.required'  => 'Jenis petugas wajib dipilih.',
            'puskesmas_id.required'     => 'Puskesmas wajib dipilih.',
        ]);

        // Buat username unik dari email
        $username = $this->generateUsername($validatedData['email']);

        // Hash password default dengan username
        $password = Hash::make($username);

        // Simpan data user petugas ke tabel `users`
        $user = User::create([
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'],
            'username'      => $username,
            'password'      => $password,
            'phone'         => $validatedData['phone'],
            'gender'        => $validatedData['gender'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'user_type_id'  => 3, // 3 = Petugas
            'is_active'     => false // Default tidak aktif
        ]);

        // Simpan detail petugas ke tabel `officers`
        Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => $validatedData['officer_type_id'],
            'district_id'     => null, // PJTB dan Kader tidak membutuhkan district
            'puskesmas_id'    => $validatedData['puskesmas_id'],
        ]);

        // Respon sukses
        return response()->json([
            'message' => 'Registrasi petugas berhasil.',
            'user'    => $user,
            'info'    => 'Username dan password awal Anda adalah: ' . $username,
            'note'    => 'Akun Anda masih menunggu verifikasi dari admin sebelum bisa login.'
        ], 201);
    }

    // Fungsi untuk generate username dari email secara unik
    private function generateUsername($email)
    {
        // Ambil bagian sebelum "@" dan hilangkan karakter non-alfanumerik
        $base = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
        $username = $base;
        $counter = 1;

        // Ulangi jika username sudah digunakan
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter++;
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
}
