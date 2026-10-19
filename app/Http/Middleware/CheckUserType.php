<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $userTypes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $userTypes)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Pasien (user_type_id == 2) ditolak secara tegas dari Web Admin dan session-nya dibersihkan
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

            $allowedTypes = explode('-', $userTypes);

            if (!in_array((string)$user->user_type_id, $allowedTypes)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'Akses ditolak.'], 403);
                }

                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
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
