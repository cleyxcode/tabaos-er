<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordAkunRequest;
use App\Http\Requests\Api\ResetPasswordAkunRequest;
use App\Models\AkunRelawan;
use App\Services\OtpPasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'errors'  => ['email' => ['Email atau password salah.']],
            ], 401);
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
                    'umur'        => $akun->relawan->umur,
                    'alamat'      => $akun->relawan->alamat,
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

    public function forgotPassword(ForgotPasswordAkunRequest $request, OtpPasswordResetService $otpService): JsonResponse
    {
        $akun = AkunRelawan::where('email', $request->email)->first();

        if (! $akun) {
            return response()->json([
                'success' => false,
                'message' => 'Email relawan tidak ditemukan.',
            ], 404);
        }

        $otpService->sendOtp($request->email, 'akun_relawan', AkunRelawan::class);

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ]);
    }

    public function resetPassword(ResetPasswordAkunRequest $request, OtpPasswordResetService $otpService): JsonResponse
    {
        try {
            $otpService->resetPassword(
                $request->email,
                $request->otp,
                $request->password,
                'akun_relawan',
                AkunRelawan::class,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login dengan password baru.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $akun = $request->user('akun_relawan')
            ->load(['relawan.pengguna']);

        return response()->json([
            'success'      => true,
            'akun_relawan' => [
                'id'     => $akun->id,
                'email'  => $akun->email,
                'status' => $akun->status,
                'relawan' => $akun->relawan ? [
                    'id'         => $akun->relawan->id,
                    'nama'       => $akun->relawan->pengguna?->name,
                    'keahlian'   => $akun->relawan->keahlian,
                    'organisasi' => $akun->relawan->organisasi,
                    'umur'       => $akun->relawan->umur,
                    'alamat'     => $akun->relawan->alamat,
                ] : null,
            ],
        ]);
    }
}
