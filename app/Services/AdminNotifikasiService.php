<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\KirimNotifikasiAdminDTO;
use App\Models\AkunFaskes;
use App\Models\AkunRelawan;
use App\Models\NotifikasiAdmin;
use App\Models\NotifikasiAdminPenerima;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class AdminNotifikasiService
{
    public function __construct(
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function buatDanKirim(KirimNotifikasiAdminDTO $dto): NotifikasiAdmin
    {
        if (! $dto->hasTarget()) {
            throw new \InvalidArgumentException('Pilih minimal satu penerima relawan atau faskes.');
        }

        $notifikasi = NotifikasiAdmin::create([
            'admin_id' => $dto->adminId,
            'judul' => $dto->judul,
            'pesan' => $dto->pesan,
            'gambar' => $dto->gambar,
            'kirim_ke_relawan' => $dto->kirimKeRelawan,
            'kirim_ke_faskes' => $dto->kirimKeFaskes,
            'kirim_semua_relawan' => $dto->kirimSemuaRelawan,
            'kirim_semua_faskes' => $dto->kirimSemuaFaskes,
            'akun_relawan_ids' => $dto->kirimKeRelawan && ! $dto->kirimSemuaRelawan
                ? array_values($dto->akunRelawanIds)
                : null,
            'akun_faskes_ids' => $dto->kirimKeFaskes && ! $dto->kirimSemuaFaskes
                ? array_values($dto->akunFaskesIds)
                : null,
            'status' => 'draft',
        ]);

        return $this->kirim($notifikasi);
    }

    public function kirim(NotifikasiAdmin $notifikasi): NotifikasiAdmin
    {
        if ($notifikasi->status === 'terkirim') {
            return $notifikasi;
        }

        try {
            $jumlah = DB::transaction(function () use ($notifikasi): int {
                $total = 0;

                if ($notifikasi->kirim_ke_relawan) {
                    $total += $this->kirimKeRelawan($notifikasi);
                }

                if ($notifikasi->kirim_ke_faskes) {
                    $total += $this->kirimKeFaskes($notifikasi);
                }

                $notifikasi->update([
                    'status' => 'terkirim',
                    'jumlah_penerima' => $total,
                    'dikirim_at' => now(),
                ]);

                return $total;
            });

            if ($jumlah === 0) {
                $notifikasi->update(['status' => 'gagal']);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal kirim notifikasi admin: ' . $e->getMessage(), [
                'notifikasi_admin_id' => $notifikasi->id,
            ]);
            $notifikasi->update(['status' => 'gagal']);
        }

        return $notifikasi->fresh(['admin']);
    }

    private function kirimKeRelawan(NotifikasiAdmin $notifikasi): int
    {
        $query = AkunRelawan::query()->where('status', 'aktif');

        if (! $notifikasi->kirim_semua_relawan && filled($notifikasi->akun_relawan_ids)) {
            $query->whereIn('id', $notifikasi->akun_relawan_ids);
        }

        return $this->distribusi($notifikasi, $query->get(), AkunRelawan::class);
    }

    private function kirimKeFaskes(NotifikasiAdmin $notifikasi): int
    {
        $query = AkunFaskes::query()->where('status', 'aktif');

        if (! $notifikasi->kirim_semua_faskes && filled($notifikasi->akun_faskes_ids)) {
            $query->whereIn('id', $notifikasi->akun_faskes_ids);
        }

        return $this->distribusi($notifikasi, $query->get(), AkunFaskes::class);
    }

    /**
     * @param  Collection<int, AkunRelawan>|Collection<int, AkunFaskes>  $akunList
     */
    private function distribusi(NotifikasiAdmin $notifikasi, Collection $akunList, string $penerimaType): int
    {
        $count = 0;
        $gambarUrl = $this->gambarUrl($notifikasi->gambar);

        foreach ($akunList as $akun) {
            NotifikasiAdminPenerima::create([
                'notifikasi_admin_id' => $notifikasi->id,
                'penerima_type' => $penerimaType,
                'penerima_id' => $akun->id,
                'sudah_dibaca' => false,
            ]);

            if ($akun->fcm_token) {
                $this->notifikasi->kirimPush(
                    token: $akun->fcm_token,
                    title: $notifikasi->judul,
                    body: $notifikasi->pesan,
                    data: [
                        'type' => 'pesan_admin',
                        'notifikasi_admin_id' => (string) $notifikasi->id,
                        'judul' => $notifikasi->judul,
                        'pesan' => $notifikasi->pesan,
                        'gambar_url' => $gambarUrl ?? '',
                    ],
                );
            }

            $count++;
        }

        return $count;
    }

    public function gambarUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
