<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ], [
            'username.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 1. Periksa kredensial melalui Auth::attempt
        if (!Auth::attempt([$field => $credentials['username'], 'password' => $credentials['password']])) {
            $errorMsg = 'Login Gagal! Username atau password tidak sesuai.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg)->withInput($request->only('username'));
        }

        $user = Auth::user();

        // 2. Pastikan akun dalam status aktif (jika terdapat kolom is_active)
        if (isset($user->is_active) && !$user->is_active) {
            if (Auth::guard('web') instanceof SessionGuard) {
                Auth::guard('web')->logout();
            } elseif (method_exists(Auth::guard(), 'logout')) {
                Auth::guard()->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $errorMsg = 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        // 3. Tolak akun Pasien (user_type_id == 2) dari Web Admin
        if ($user->user_type_id == 2) {
            if (Auth::guard('web') instanceof SessionGuard) {
                Auth::guard('web')->logout();
            } elseif (method_exists(Auth::guard(), 'logout')) {
                Auth::guard()->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $errorMsg = 'Akun pasien tidak dapat digunakan untuk login melalui Web Admin. Silakan gunakan aplikasi TB Care mobile.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        // 4. Tolak akun Kader (officer_type_id == 4) dari Web Admin
        if ($user->user_type_id == 3 && $user->officer && $user->officer->isKader()) {
            if (Auth::guard('web') instanceof SessionGuard) {
                Auth::guard('web')->logout();
            } elseif (method_exists(Auth::guard(), 'logout')) {
                Auth::guard()->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $errorMsg = 'Akun kader tidak dapat digunakan untuk login melalui Web Admin. Silakan gunakan aplikasi TB Care mobile.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        // 5. Pastikan hanya Administrator (1) dan Petugas Berwenang (3: Dinkes & PJTB) yang diizinkan
        if (!in_array($user->user_type_id, [1, 3])) {
            if (Auth::guard('web') instanceof SessionGuard) {
                Auth::guard('web')->logout();
            } elseif (method_exists(Auth::guard(), 'logout')) {
                Auth::guard()->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $errorMsg = 'Peran akun Anda tidak memiliki hak akses ke Web Admin.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        // 5. Autentikasi berhasil: regenerasi session ID & simpan role ke session
        $request->session()->regenerate();
        session(['role' => $user->user_type_id]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::guard('web') instanceof SessionGuard) {
            Auth::guard('web')->logout();
        } elseif (method_exists(Auth::guard(), 'logout')) {
            Auth::guard()->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/')->with([
            'logout' => true,
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
