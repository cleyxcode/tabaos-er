<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAdminApiKey
{
    public function handle(Request $request, Closure $next): mixed
    {
        $configuredKey = config('services.admin_api.key');

        if (! $configuredKey) {
            return response()->json([
                'success' => false,
                'message' => 'Admin API key belum dikonfigurasi.',
            ], 503);
        }

        $providedKey = $request->header('X-Admin-Key');

        if (! $providedKey || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses admin ditolak.',
            ], 401);
        }

        return $next($request);
    }
}
