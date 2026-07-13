<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfilRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\Api\PenggunaResource;
use App\Models\Pengguna;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\OtpPasswordResetService;

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

        $pengguna = Pengguna::with('relawan.akunRelawan')->where($field, $value)->first();

        if (! $pengguna || ! Hash::check($request->password, $pengguna->password)) {
            return $this->error('Nomor telepon/email atau password salah.', 401);
        }

        // Blokir login masyarakat jika sudah diverifikasi menjadi relawan
        if (
            $pengguna->relawan
            && $pengguna->relawan->status === 'disetujui'
            && $pengguna->relawan->akunRelawan
        ) {
            return $this->error(
                'Akun Anda sudah ditingkatkan menjadi relawan. Silakan login melalui tab Relawan di halaman masuk.',
                403
            );
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
        $pengguna->update($request->only('name', 'email', 'phone'));

        return $this->success(
            new PenggunaResource($pengguna->fresh()),
            'Profil berhasil diperbarui.'
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request, OtpPasswordResetService $otpService): JsonResponse
    {
        $otpService->sendOtp($request->email, 'pengguna', Pengguna::class);

        return $this->success(null, 'Kode OTP telah dikirim ke email Anda.');
    }

    public function resetPassword(ResetPasswordRequest $request, OtpPasswordResetService $otpService): JsonResponse
    {
        try {
            $otpService->resetPassword(
                $request->email,
                $request->otp,
                $request->password,
                'pengguna',
                Pengguna::class,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}
