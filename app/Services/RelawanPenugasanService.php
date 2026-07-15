<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;

class RelawanPenugasanService
{
    /**
     * Radius default untuk pulau/daerah Maluku.
     * Cukup untuk satu kota/pulau, tidak menyeberang antar kepulauan jauh.
     */
    public const DEFAULT_RADIUS_KM = 75.0;

    public function __construct(
        protected HaversineService $haversine,
        protected WilayahLokasiService $wilayahLokasi,
    ) {}

    /**
     * Cari satu relawan aktif terdekat dari koordinat laporan.
     *
     * Prioritas cakupan:
     * 1. Pulau yang sama (jika terdeteksi)
     * 2. Kota yang sama
     * 3. Provinsi yang sama (fallback)
     *
     * Hanya kandidat dalam radiusKm yang dipertimbangkan, lalu diambil yang terdekat.
     */
    public function cariRelawanTerdekat(
        float $lat,
        float $lng,
        ?string $provinsi = null,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
        ?string $pulau = null,
        ?string $kota = null,
    ): ?AkunRelawan {
        $wilayahLaporan = $this->resolveWilayahScope($lat, $lng, $provinsi, $pulau, $kota);
        $provinsi = $wilayahLaporan['provinsi'];
        $pulau = $wilayahLaporan['pulau'];
        $kota = $wilayahLaporan['kota'];

        $kandidat = AkunRelawan::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (AkunRelawan $akun) use ($lat, $lng) {
                $jarak = $this->haversine->hitungJarak(
                    $lat,
                    $lng,
                    (float) $akun->latitude,
                    (float) $akun->longitude,
                );
                $akun->setAttribute('jarak_ke_laporan_km', $jarak);

                return $akun;
            })
            ->filter(fn (AkunRelawan $akun) => (float) $akun->jarak_ke_laporan_km <= $radiusKm)
            ->filter(function (AkunRelawan $akun) use ($provinsi, $pulau, $kota) {
                return $this->relawanDalamCakupanLaporan(
                    $akun,
                    $provinsi,
                    $pulau,
                    $kota,
                );
            })
            ->sortBy(fn (AkunRelawan $akun) => (float) $akun->jarak_ke_laporan_km)
            ->first();

        return $kandidat instanceof AkunRelawan ? $kandidat : null;
    }

    /**
     * Tugaskan laporan ke satu relawan terdekat dan kembalikan akun relawan tersebut.
     */
    public function tugaskanRelawanTerdekat(
        LaporanBencana $laporan,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
    ): ?AkunRelawan {
        if ($laporan->latitude === null || $laporan->longitude === null) {
            return null;
        }

        if ($laporan->akun_relawan_ditugaskan !== null) {
            return AkunRelawan::find($laporan->akun_relawan_ditugaskan);
        }

        $laporan->loadMissing('wilayah');
        $wilayah = $laporan->wilayah;

        $akun = $this->cariRelawanTerdekat(
            (float) $laporan->latitude,
            (float) $laporan->longitude,
            $wilayah?->provinsi,
            $radiusKm,
            $wilayah?->pulau,
            $wilayah?->kota,
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

    /**
     * @return array{provinsi: ?string, pulau: ?string, kota: ?string}
     */
    private function resolveWilayahScope(
        float $lat,
        float $lng,
        ?string $provinsi,
        ?string $pulau,
        ?string $kota,
    ): array {
        if ($provinsi !== null && $pulau !== null && $kota !== null) {
            return compact('provinsi', 'pulau', 'kota');
        }

        $detected = $this->wilayahLokasi->cariWilayahTerdekat($lat, $lng);

        return [
            'provinsi' => $provinsi ?? $detected?->provinsi,
            'pulau' => $pulau ?? $detected?->pulau,
            'kota' => $kota ?? $detected?->kota,
        ];
    }

    private function relawanDalamCakupanLaporan(
        AkunRelawan $akun,
        ?string $provinsi,
        ?string $pulau,
        ?string $kota,
    ): bool {
        $akunWilayah = $this->wilayahLokasi->cariWilayahTerdekat(
            (float) $akun->latitude,
            (float) $akun->longitude,
        );

        if ($akunWilayah === null) {
            // Tanpa data wilayah referensi, izinkan hanya jika cakupan laporan juga tidak spesifik.
            return $pulau === null && $kota === null && $provinsi === null;
        }

        if ($pulau !== null && filled($akunWilayah->pulau)) {
            return $akunWilayah->pulau === $pulau;
        }

        if ($kota !== null && filled($akunWilayah->kota)) {
            return $akunWilayah->kota === $kota;
        }

        if ($provinsi !== null && filled($akunWilayah->provinsi)) {
            return $akunWilayah->provinsi === $provinsi;
        }

        return true;
    }
}
