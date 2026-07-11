<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Resources\LaporanBencanaResource;
use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class RelawanKedatanganService
{
    /** Radius kedatangan relawan di lokasi laporan (km). */
    public const RADIUS_KM = 0.15;

    public function __construct(
        private readonly HaversineService $haversine,
    ) {}

    public function periksaDanBeritahuAdmin(AkunRelawan $akun, float $latitude, float $longitude): void
    {
        $akun->loadMissing('relawan.pengguna');

        $laporanList = LaporanBencana::query()
            ->where('akun_relawan_ditugaskan', $akun->id)
            ->where('status_penanganan', 'sedang_ditangani')
            ->whereNull('relawan_sampai_notified_at')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($laporanList as $laporan) {
            $jarak = $this->haversine->hitungJarak(
                $latitude,
                $longitude,
                (float) $laporan->latitude,
                (float) $laporan->longitude,
            );

            if ($jarak > self::RADIUS_KM) {
                continue;
            }

            $this->kirimNotifikasiAdmin($akun, $laporan, $jarak);
            $laporan->update(['relawan_sampai_notified_at' => now()]);
        }
    }

    public function sudahSampai(
        float $relawanLat,
        float $relawanLng,
        float $laporanLat,
        float $laporanLng,
        ?float $radiusKm = null,
    ): bool {
        $radius = $radiusKm ?? self::RADIUS_KM;

        return $this->haversine->hitungJarak(
            $relawanLat,
            $relawanLng,
            $laporanLat,
            $laporanLng,
        ) <= $radius;
    }

    private function kirimNotifikasiAdmin(AkunRelawan $akun, LaporanBencana $laporan, float $jarakKm): void
    {
        $namaRelawan = $akun->relawan?->pengguna?->name ?? 'Relawan';
        $alamat = $laporan->alamat_lokasi ?? 'lokasi laporan';
        $jarakMeter = max(1, (int) round($jarakKm * 1000));

        $users = User::query()->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Relawan Tiba di Lokasi')
            ->icon('heroicon-o-map-pin')
            ->success()
            ->body("{$namaRelawan} telah sampai di lokasi laporan {$laporan->jenis_kejadian} ({$alamat}). Jarak: ~{$jarakMeter} m.")
            ->actions([
                Action::make('lihat_laporan')
                    ->label('Lihat Laporan')
                    ->url(LaporanBencanaResource::getUrl('edit', ['record' => $laporan]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($users);
    }
}
