<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function index()
    {
        if (UserType::all()->isEmpty()) {
            UserType::insert([
                ['name' => 'Super Admin'],
                ['name' => 'Admin Dinkes'],
                ['name' => 'Koordinator'],
                ['name' => 'Pasien']
            ]);

            if (User::all()->isEmpty()) {
                User::create([
                    'name' => 'Alamsyah Firdaus',
                    'email' => 'alamsyah.firdaus.af31@gmail.com',
                    'username' => 'alamsyah',
                    'gender' => 'Laki-laki',
                    'place_of_birth' => 'Tasikmalaya',
                    'date_of_birth' => '1998-07-31',
                    'telephone' => '089693839624',
                    'password' => Hash::make('alamsyah'),
                    'user_type_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return view('login');
    }


    public function authenticate(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            $user = Auth::user();

            session(['role' =>  $user->user_type_id]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Login Gagal!'], 422);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with([
            'logout' => true,
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
