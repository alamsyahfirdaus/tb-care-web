<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View; // Add this import if not already present

class LoginController extends Controller
{
    public function index(): View // Change the return type hint to View
    {
        return view('login');
    }

    public function authenticate(Request $request): JsonResponse
    {
        // Memvalidasi kredensial yang diterima dari permintaan, memastikan 'username' dan 'password' diisi.
        $credentials = $request->validate([
            'username' => ['required'],  // Mengharuskan kolom username ada
            'password' => ['required'],  // Mengharuskan kolom password ada
        ]);

        // Menentukan field yang digunakan untuk autentikasi, apakah 'email' atau 'username', berdasarkan format username.
        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Mencoba melakukan autentikasi pengguna dengan kredensial yang diberikan.
        if (Auth::attempt([$field => $credentials['username'], 'password' => $credentials['password']])) {
            // Jika berhasil, regenerasi session untuk mencegah serangan session fixation.
            $request->session()->regenerate();
            // Mengembalikan respons JSON dengan status sukses.
            return response()->json(['success' => true]);
        }

        // Jika autentikasi gagal, mengembalikan respons JSON dengan pesan error.
        return response()->json(['error' => 'Login Gagal!'], 422);
    }

    public function logout(Request $request): RedirectResponse
    {
        // Mengeluarkan pengguna dari sesi autentikasi.
        Auth::logout();

        // Menginvalide session saat ini untuk mencegah akses lebih lanjut setelah logout.
        $request->session()->invalidate();

        // Regenerasi token CSRF untuk meningkatkan keamanan.
        $request->session()->regenerateToken();

        // Mengalihkan pengguna kembali ke halaman login dengan notifikasi logout berhasil.
        return redirect('login')->with([
            'logout' => true,  // Menambahkan flag logout untuk memberi tahu bahwa pengguna telah keluar
        ])->withHeaders([
            // Mengatur header untuk mencegah cache di browser.
            'Cache-Control' => 'no-cache, no-store, must-revalidate', // Mencegah caching
            'Pragma' => 'no-cache',  // Mencegah caching untuk HTTP/1.0
            'Expires' => '0',  // Menandakan bahwa konten sudah kadaluarsa
        ]);
    }
}
