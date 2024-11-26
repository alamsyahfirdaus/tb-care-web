<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    public function handle(Request $request, Closure $next, $userTypes)
    {
        if (Auth::check()) {
            $allowedTypes = explode('-', $userTypes);

            if (!in_array(Auth::user()->user_type_id, $allowedTypes)) {
                return redirect('/');
            }
        } else {
            return redirect('/');
        }

        return $next($request);
    }
}
