<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureWebAdminRole
{
    /**
     * Handle an incoming request.
     *
     * Memastikan hanya peran Administrator (user_type_id = 1) dan Petugas (user_type_id = 3)
     * yang diizinkan mengakses antarmuka Web Admin.
     * Akun Pasien (user_type_id = 2) ditolak secara tegas, session web dibersihkan seketika
     * untuk mencegah akses tidak sah maupun potensi redirect loop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Tolak role Pasien (user_type_id == 2)
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

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Akun pasien tidak dapat digunakan untuk Web Admin. Silakan gunakan aplikasi TB Care mobile.'
                    ], 403);
                }

                return redirect()->route('login')->with('error', 'Akun pasien tidak dapat digunakan untuk login melalui Web Admin. Silakan gunakan aplikasi TB Care mobile.');
            }

            // 2. Tolak role lain selain Administrator (1) dan Petugas (3)
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

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Peran akun Anda tidak memiliki izin akses ke Web Admin.'
                    ], 403);
                }

                return redirect()->route('login')->with('error', 'Peran akun Anda tidak memiliki izin akses ke Web Admin.');
            }
        } else {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
