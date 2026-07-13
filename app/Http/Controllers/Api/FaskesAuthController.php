<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordAkunRequest;
use App\Http\Requests\Api\ResetPasswordAkunRequest;
use App\Models\AkunFaskes;
use App\Services\OtpPasswordResetService;
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
                    'latitude'  => $akun->faskes->latitude  ? (float) $akun->faskes->latitude  : null,
                    'longitude' => $akun->faskes->longitude ? (float) $akun->faskes->longitude : null,
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

    public function forgotPassword(ForgotPasswordAkunRequest $request, OtpPasswordResetService $otpService): JsonResponse
    {
        $akun = AkunFaskes::where('email', $request->email)->first();

        if (! $akun) {
            return response()->json([
                'success' => false,
                'message' => 'Email faskes tidak ditemukan.',
            ], 404);
        }

        $otpService->sendOtp($request->email, 'akun_faskes', AkunFaskes::class);

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
                'akun_faskes',
                AkunFaskes::class,
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
        $akun = $request->user('akun_faskes')->load('faskes.ambulans');
        $faskes = $akun->faskes;

        return response()->json([
            'success'     => true,
            'akun_faskes' => [
                'id'           => $akun->id,
                'nama_petugas' => $akun->nama_petugas,
                'email'        => $akun->email,
                'status'       => $akun->status,
                'faskes'       => $faskes ? [
                    'id'        => $faskes->id,
                    'nama'      => $faskes->nama,
                    'tipe'      => $faskes->tipe,
                    'alamat'    => $faskes->alamat,
                    'latitude'  => $faskes->latitude  ? (float) $faskes->latitude  : null,
                    'longitude' => $faskes->longitude ? (float) $faskes->longitude : null,
                    'ambulans'  => $faskes->ambulans->map(fn ($a) => [
                        'id'           => $a->id,
                        'plat_nomor'   => $a->plat_nomor,
                        'status'       => $a->status,
                        'no_telepon'   => $a->no_telepon,
                    ])->values(),
                ] : null,
            ],
        ]);
    }
}
