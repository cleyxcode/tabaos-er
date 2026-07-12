<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class HaversineService
{
    /**
     * Hitung jarak antara dua koordinat dalam kilometer.
     */
    public function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Tambahkan kolom jarak_km dan filter radius pada query builder.
     * Contoh pemakaian: LaporanBencana::query()->tap(fn($q) => $haversine->scopeQuery($q, $lat, $lng, 10))
     */
    public function scopeQuery($query, float $lat, float $lng, float $radiusKm = 10)
    {
        $acosInput = $this->acosInputExpression();
        $distanceSql = "( 6371 * acos({$acosInput}))";

        return $query
            ->selectRaw("*, {$distanceSql} AS jarak_km", [$lat, $lng, $lat])
            ->whereRaw("{$distanceSql} <= ?", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('jarak_km');
    }

    private function acosInputExpression(): string
    {
        $expression = 'cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))';

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "MIN(1, MAX(-1, {$expression}))",
            default => "LEAST(1, GREATEST(-1, {$expression}))",
        };
    }
}
