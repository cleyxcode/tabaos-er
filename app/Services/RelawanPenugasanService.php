<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;

class RelawanPenugasanService
{
    public function __construct(
        protected HaversineService $haversine,
        protected WilayahLokasiService $wilayahLokasi,
    ) {}

    /**
     * Cari satu relawan aktif terdekat dari koordinat laporan.
     * Hanya relawan dalam provinsi yang sama yang dipertimbangkan.
     */
    public function cariRelawanTerdekat(
        float $lat,
        float $lng,
        ?string $provinsi = null,
        float $radiusKm = 500,
    ): ?AkunRelawan {
        $provinsi ??= $this->wilayahLokasi->deteksiProvinsi($lat, $lng);

        $kandidat = AkunRelawan::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->filter(function (AkunRelawan $akun) use ($lat, $lng, $provinsi, $radiusKm) {
                if ($provinsi !== null) {
                    $akunProvinsi = $this->wilayahLokasi->deteksiProvinsi(
                        (float) $akun->latitude,
                        (float) $akun->longitude,
                    );

                    if ($akunProvinsi !== null && $akunProvinsi !== $provinsi) {
                        return false;
                    }
                }

                $jarak = $this->haversine->hitungJarak(
                    $lat,
                    $lng,
                    (float) $akun->latitude,
                    (float) $akun->longitude,
                );

                return $jarak <= $radiusKm;
            })
            ->sortBy(fn (AkunRelawan $akun) => $this->haversine->hitungJarak(
                $lat,
                $lng,
                (float) $akun->latitude,
                (float) $akun->longitude,
            ))
            ->first();

        return $kandidat instanceof AkunRelawan ? $kandidat : null;
    }

    /**
     * Tugaskan laporan ke satu relawan terdekat dan kembalikan akun relawan tersebut.
     */
    public function tugaskanRelawanTerdekat(LaporanBencana $laporan, float $radiusKm = 500): ?AkunRelawan
    {
        if ($laporan->latitude === null || $laporan->longitude === null) {
            return null;
        }

        if ($laporan->akun_relawan_ditugaskan !== null) {
            return AkunRelawan::find($laporan->akun_relawan_ditugaskan);
        }

        $provinsi = $laporan->wilayah?->provinsi
            ?? $this->wilayahLokasi->deteksiProvinsi((float) $laporan->latitude, (float) $laporan->longitude);

        $akun = $this->cariRelawanTerdekat(
            (float) $laporan->latitude,
            (float) $laporan->longitude,
            $provinsi,
            $radiusKm,
        );

        if ($akun === null) {
            return null;
        }

        $laporan->update(['akun_relawan_ditugaskan' => $akun->id]);

        return $akun;
    }

    public function relawanBerhakAksesLaporan(AkunRelawan $akun, LaporanBencana $laporan): bool
    {
        if ($laporan->akun_relawan_ditugaskan === null) {
            return false;
        }

        return (int) $laporan->akun_relawan_ditugaskan === (int) $akun->id;
    }
}
