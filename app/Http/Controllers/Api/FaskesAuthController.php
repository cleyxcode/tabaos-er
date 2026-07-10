<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunFaskes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FaskesAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $akun = AkunFaskes::with('faskes')->where('email', $request->email)->first();

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

        $token = $akun->createToken('akun-faskes')->plainTextToken;

        return response()->json([
            'success'     => true,
            'token'       => $token,
            'akun_faskes' => [
                'id'          => $akun->id,
                'nama_petugas' => $akun->nama_petugas,
                'email'       => $akun->email,
                'faskes'      => $akun->faskes ? [
                    'id'        => $akun->faskes->id,
                    'nama'      => $akun->faskes->nama,
                    'tipe'      => $akun->faskes->tipe,
                    'alamat'    => $akun->faskes->alamat,
                    'latitude'  => $akun->faskes->latitude,
                    'longitude' => $akun->faskes->longitude,
                ] : null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('akun_faskes')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $akun = $request->user('akun_faskes')->load('faskes.ambulans');

        return response()->json([
            'success'     => true,
            'akun_faskes' => $akun,
        ]);
    }
}
