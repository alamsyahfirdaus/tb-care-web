<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && isset($user->is_active) && !$user->is_active) {
            // Revoke current / all tokens of deactivated user
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'
            ], 403);
        }

        return $next($request);
    }
}
