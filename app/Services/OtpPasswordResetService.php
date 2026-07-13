<?php

namespace App\Services;

use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpPasswordResetService
{
    /**
     * @param  class-string  $modelClass
     */
    public function sendOtp(string $email, string $table, string $modelClass): void
    {
        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->tokenKey($table, $email)],
            [
                'token'      => $otp,
                'created_at' => now(),
            ]
        );

        Mail::to($email)->send(new OtpMail($otp));
    }

    /**
     * @param  class-string  $modelClass
     */
    public function resetPassword(
        string $email,
        string $otp,
        string $password,
        string $table,
        string $modelClass,
        string $emailColumn = 'email',
    ): void {
        $tokenKey = $this->tokenKey($table, $email);

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $tokenKey)
            ->where('token', $otp)
            ->first();

        if (! $resetRequest) {
            throw new \InvalidArgumentException('Kode OTP tidak valid.');
        }

        if (Carbon::parse($resetRequest->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $tokenKey)->delete();
            throw new \InvalidArgumentException('Kode OTP sudah kedaluwarsa.');
        }

        $account = $modelClass::where($emailColumn, $email)->first();

        if (! $account) {
            throw new \InvalidArgumentException('Akun tidak ditemukan.');
        }

        $casts = method_exists($account, 'getCasts') ? $account->getCasts() : [];
        if (($casts['password'] ?? null) === 'hashed') {
            $account->update(['password' => $password]);
        } else {
            $account->update(['password' => Hash::make($password)]);
        }

        $account->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $tokenKey)->delete();
    }

    private function tokenKey(string $table, string $email): string
    {
        return $table . ':' . $email;
    }
}
