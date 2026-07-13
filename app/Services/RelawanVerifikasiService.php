<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkunRelawan;
use App\Models\Relawan;
use App\Models\RelawanNotifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RelawanVerifikasiService
{
    public function __construct(
        private readonly NotifikasiService $notifikasi,
    ) {}

    /**
     * Verifikasi pendaftaran relawan: setujui, buat akun relawan, kirim notifikasi,
     * dan cabut token login masyarakat agar tidak bisa login sebagai masyarakat lagi.
     */
    public function verifikasi(Relawan $relawan, int $adminId, ?string $passwordBaru = null): AkunRelawan
    {
        return DB::transaction(function () use ($relawan, $adminId, $passwordBaru): AkunRelawan {
            $relawan->loadMissing(['pengguna', 'akunRelawan']);

            if ($relawan->status === 'ditolak') {
                throw new \InvalidArgumentException('Pendaftaran relawan ini sudah ditolak.');
            }

            if ($relawan->status === 'disetujui' && $relawan->akunRelawan) {
                throw new \InvalidArgumentException('Relawan ini sudah diverifikasi.');
            }

            $pengguna = $relawan->pengguna;
            if (! $pengguna) {
                throw new \InvalidArgumentException('Data pengguna relawan tidak ditemukan.');
            }

            $email = $pengguna->email ?: ($pengguna->phone.'@tabaos.local');

            if (AkunRelawan::where('email', $email)
                ->where('relawan_id', '!=', $relawan->id)
                ->exists()) {
                throw new \InvalidArgumentException('Email sudah digunakan akun relawan lain.');
            }

            $relawan->update([
                'status'      => 'disetujui',
                'approved_by' => $adminId,
            ]);

            $passwordHash = $passwordBaru !== null
                ? Hash::make($passwordBaru)
                : $pengguna->getRawOriginal('password');

            $akun = $relawan->akunRelawan ?? new AkunRelawan(['relawan_id' => $relawan->id]);
            $akun->forceFill([
                'email'    => $email,
                'password' => $passwordHash,
                'status'   => 'aktif',
            ])->save();

            $pengguna->tokens()->delete();

            $this->buatNotifikasiVerifikasi($akun);

            return $akun->fresh(['relawan.pengguna']);
        });
    }

    public function tolak(Relawan $relawan, int $adminId): Relawan
    {
        if ($relawan->status === 'disetujui') {
            throw new \InvalidArgumentException('Relawan yang sudah disetujui tidak dapat ditolak.');
        }

        $relawan->update([
            'status'      => 'ditolak',
            'approved_by' => $adminId,
        ]);

        return $relawan->fresh();
    }

    protected function buatNotifikasiVerifikasi(AkunRelawan $akun): void
    {
        $judul = 'Akun Relawan Diverifikasi';
        $pesan = 'Selamat! Pendaftaran relawan Anda telah disetujui. Silakan login melalui tab Relawan menggunakan email dan password yang sama.';

        RelawanNotifikasi::create([
            'akun_relawan_id' => $akun->id,
            'jenis'           => 'verifikasi',
            'judul'           => $judul,
            'pesan'           => $pesan,
            'laporan_id'      => null,
            'sudah_dibaca'    => false,
        ]);

        if ($akun->fcm_token) {
            $this->notifikasi->kirimPush(
                token: $akun->fcm_token,
                title: $judul,
                body: $pesan,
                data: ['type' => 'verifikasi_relawan'],
            );
        }
    }
}
