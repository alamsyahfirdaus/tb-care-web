<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'profile');
        $user = Auth::user();

        // Batasi tab roles dan system hanya untuk Super Administrator
        if (in_array($tab, ['roles', 'system']) && !$user->isSuperAdmin()) {
            $tab = 'profile';
        }

        $userTypes = UserType::withCount('users')->get();

        return view('admin.settings.index', compact('tab', 'user', 'userTypes'))->with([
            'title'        => 'Pengaturan Sistem & Profil Admin',
            'pageTitle'    => 'Pengaturan TB Care',
            'pageSubtitle' => 'Kelola akun administrator, tinjau matriks hak akses pengguna, dan preferensi aplikasi.'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'password'     => 'nullable|string|min:6|confirmed',
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        $user->name         = $request->name;
        $user->email        = $request->email;
        $user->phone        = $request->phone_number ?? $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        ActivityLog::log('Perbarui Profil', 'Pengaturan', "Administrator {$user->name} memperbarui profil dan keamanan akun.");

        return back()->with('success', 'Profil administrator berhasil diperbarui.');
    }

    public function updateSystem(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Hanya Administrator yang memiliki akses mengubah pengaturan sistem.');
        }

        $request->validate([
            'hotline_whatsapp' => 'nullable|string|max:50',
        ]);

        if ($request->filled('hotline_whatsapp')) {
            SystemSetting::set('hotline_whatsapp', $request->hotline_whatsapp);
        }

        ActivityLog::log('Perbarui Pengaturan Sistem', 'Pengaturan', 'Memperbarui konfigurasi sistem aplikasi TB Care.');

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
