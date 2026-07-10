<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AkunAktif
{
    public function handle(Request $request, Closure $next, string $guard): mixed
    {
        $user = $request->user($guard);

        if (! $user || $user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak aktif. Hubungi administrator.',
            ], 403);
        }

        return $next($request);
    }
}
