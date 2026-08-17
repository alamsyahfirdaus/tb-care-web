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
            ? $user->photo
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

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:255',
            'email'         => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'         => 'sometimes|string|min:10|max:15',
            'gender'        => 'sometimes|in:L,P',
            'place_of_birth'=> 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date|before:today',
            'password'      => 'sometimes|string|min:6',
            'photo'         => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // === FOTO PROFIL ===
        if ($request->hasFile('photo')) {

            if (!empty($user->photo)) {
                $oldPath = public_path('images/' . $user->photo);
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $destinationPath = public_path('images');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = Str::random(20) . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->move($destinationPath, $fileName);

            $data['photo'] = $fileName;
        }

        $user->update($data);

        $user->photo = $user->photo
            ? $user->photo
            : null;

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user,
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }

        return response()->json([
            'message' => 'Token FCM berhasil diperbarui.',
        ]);
    }
}
