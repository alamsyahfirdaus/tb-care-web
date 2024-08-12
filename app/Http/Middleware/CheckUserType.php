<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Memeriksa apakah pengguna sudah terautentikasi
        if (Auth::check()) {
            // Memeriksa apakah user_type_id pengguna adalah 4
            if (Auth::user()->user_type_id == 4) {
                // Menghapus semua data sesi
                $request->session()->flush();

                // Mengalihkan ke halaman login dengan pesan error
                return redirect('login')->with('error', 'Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
        } else {
            // Mengalihkan ke halaman login jika pengguna belum terautentikasi
            return redirect('login');
        }

        // Melanjutkan ke permintaan berikutnya jika tidak ada masalah
        return $next($request);
    }
}
