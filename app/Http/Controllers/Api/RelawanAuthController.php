<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunRelawan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RelawanAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $akun = AkunRelawan::where('email', $request->email)->first();

        if (! $akun || ! Hash::check($request->password, $akun->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($akun->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak aktif. Hubungi administrator.',
            ], 403);
        }

        $token = $akun->createToken('akun-relawan')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'akun_relawan' => [
                'id'     => $akun->id,
                'email'  => $akun->email,
                'status' => $akun->status,
                'relawan' => $akun->relawan ? [
                    'id'          => $akun->relawan->id,
                    'nama'        => $akun->relawan->pengguna?->name,
                    'keahlian'    => $akun->relawan->keahlian,
                    'organisasi'  => $akun->relawan->organisasi ?? null,
                ] : null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('akun_relawan')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $akun = $request->user('akun_relawan')
            ->load(['relawan.pengguna']);

        return response()->json([
            'success'      => true,
            'akun_relawan' => $akun,
        ]);
    }
}
