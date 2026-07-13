<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wilayah;

class WilayahLokasiService
{
    public function __construct(protected HaversineService $haversine) {}

    public function cariWilayahTerdekat(float $lat, float $lng): ?Wilayah
    {
        $result = Wilayah::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Wilayah $wilayah) => [
                'wilayah' => $wilayah,
                'jarak' => $this->haversine->hitungJarak(
                    $lat,
                    $lng,
                    (float) $wilayah->latitude,
                    (float) $wilayah->longitude,
                ),
            ])
            ->sortBy('jarak')
            ->first();

        return $result['wilayah'] ?? null;
    }

    public function deteksiKota(float $lat, float $lng): ?string
    {
        return $this->cariWilayahTerdekat($lat, $lng)?->kota;
    }

    public function deteksiProvinsi(float $lat, float $lng): ?string
    {
        return $this->cariWilayahTerdekat($lat, $lng)?->provinsi;
    }
}
