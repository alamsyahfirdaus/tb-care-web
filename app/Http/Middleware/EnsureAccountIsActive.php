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

        if ($user) {
            // Re-query status aktif terkini langsung dari database untuk memastikan perubahan admin segera efektif
            if (isset($user->id)) {
                $currentStatus = \App\Models\User::where('id', $user->id)->value('is_active');
                $user->is_active = (bool) $currentStatus;
            }

            if (isset($user->is_active) && !$user->is_active) {
                // Revoke current / all tokens of deactivated user
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                return response()->json([
                    'success' => false,
                    'code'    => 'ACCOUNT_DEACTIVATED',
                    'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi petugas TB Care untuk informasi lebih lanjut.'
                ], 403);
            }
        }

        return $next($request);
    }
}
