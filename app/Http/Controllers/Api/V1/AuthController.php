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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

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
        $pengguna->update($request->only('name', 'email', 'phone'));

        return $this->success(
            new PenggunaResource($pengguna->fresh()),
            'Profil berhasil diperbarui.'
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $otp = (string) rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => $otp,
                'created_at' => now(),
            ]
        );

        Mail::to($request->email)->send(new OtpMail($otp));

        return $this->success(null, 'Kode OTP telah dikirim ke email Anda.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (! $resetRequest) {
            return $this->error('Kode OTP tidak valid.', 400);
        }

        // Cek apakah OTP sudah expired (10 menit)
        if (Carbon::parse($resetRequest->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->error('Kode OTP sudah kedaluwarsa.', 400);
        }

        $pengguna = Pengguna::where('email', $request->email)->first();

        if (! $pengguna) {
            return $this->error('Pengguna tidak ditemukan.', 404);
        }

        // password akan otomatis di hash karena ada casts => ['password' => 'hashed'] di Model Pengguna
        $pengguna->update([
            'password' => $request->password,
        ]);

        // Revoke all old tokens just in case
        $pengguna->tokens()->delete();

        // Hapus token reset setelah berhasil
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->success(null, 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}
