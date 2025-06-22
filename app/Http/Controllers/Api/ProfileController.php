<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show()
    {
        // Ambil data user yang sedang login
        $user = Auth::user();

        // Tambahkan URL lengkap ke gambar profil jika ada
        $user->photo = $user->photo
            ? URL::to('/') . '/storage/images/' . $user->photo
            : null;

        // Kembalikan data profil dalam format JSON
        return response()->json([
            'message' => 'Data profil berhasil diambil.',
            'data'    => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'      => 'sometimes|string|min:10|max:15',
            'gender'         => 'sometimes|in:L,P',
            'tanggal_lahir'  => 'sometimes|date|before:today',
            'password'       => 'sometimes|string|min:6',
            'photo'          => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'name.string'           => 'Nama harus berupa teks.',
            'name.max'              => 'Nama maksimal 255 karakter.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan.',
            'phone.min'         => 'Nomor HP minimal 10 digit.',
            'phone.max'         => 'Nomor HP maksimal 15 digit.',
            'gender.in'             => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
            'tanggal_lahir.date'    => 'Tanggal lahir harus berupa tanggal yang valid.',
            'tanggal_lahir.before'  => 'Tanggal lahir harus sebelum hari ini.',
            'password.min'          => 'Password minimal 6 karakter.',
            'photo.image'           => 'File harus berupa gambar.',
            'photo.mimes'           => 'Format gambar harus JPG, JPEG, atau PNG.',
            'photo.max'             => 'Ukuran gambar maksimal 2 MB.',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Hash password jika ada
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Upload foto baru jika ada
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($user->photo && Storage::disk('public')->exists('images/' . $user->photo)) {
                Storage::disk('public')->delete('images/' . $user->photo);
            }

            // Simpan foto baru
            $fileName = Str::random(20) . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->storeAs('images', $fileName, 'public');
            $data['photo'] = $fileName;
        }

        // Update user
        $user->update($data);

        // Tambahkan URL untuk photo
        $user->photo = $user->photo
            ? URL::to('/') . '/storage/images/' . $user->photo
            : null;

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user,
        ]);
    }
}
