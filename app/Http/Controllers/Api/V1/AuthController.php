<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfilRequest;
use App\Http\Resources\Api\PenggunaResource;
use App\Models\Pengguna;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $pengguna = Pengguna::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        $token = $pengguna->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'pengguna' => new PenggunaResource($pengguna),
            'token'    => $token,
        ], 'Registrasi berhasil.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // Support login via phone OR email
        $field = $request->filled('phone') ? 'phone' : 'email';
        $value = $request->input($field);

        $pengguna = Pengguna::where($field, $value)->first();

        if (! $pengguna || ! Hash::check($request->password, $pengguna->password)) {
            return $this->error('Nomor telepon/email atau password salah.', 401);
        }

        // Revoke all old tokens and issue fresh one
        $pengguna->tokens()->delete();
        $token = $pengguna->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'pengguna' => new PenggunaResource($pengguna),
            'token'    => $token,
        ], 'Login berhasil.');
    }

    public function logout(): JsonResponse
    {
        auth('pengguna')->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    public function me(): JsonResponse
    {
        return $this->success(
            new PenggunaResource(auth('pengguna')->user()),
            'Data profil berhasil diambil.'
        );
    }

    public function updateProfil(UpdateProfilRequest $request): JsonResponse
    {
        $pengguna = auth('pengguna')->user();
        $pengguna->update($request->only('name', 'email'));

        return $this->success(
            new PenggunaResource($pengguna->fresh()),
            'Profil berhasil diperbarui.'
        );
    }
}
