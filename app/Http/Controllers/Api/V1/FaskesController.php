<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaskesResource;
use App\Models\Faskes;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaskesController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Faskes::with(['wilayah', 'ambulans']);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $faskes = $query->get();

        // Sort by proximity (Haversine) if lat/lng/radius_km provided
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $radius = (float) ($request->radius_km ?? 999999);

            $faskes = $faskes->filter(function ($f) use ($lat, $lng, $radius) {
                if (! $f->latitude || ! $f->longitude) {
                    return false;
                }
                $dist = $this->haversine($lat, $lng, $f->latitude, $f->longitude);
                $f->jarak_km = $dist;
                return $dist <= $radius;
            })->sortBy('jarak_km')->values();
        }

        return $this->success(
            FaskesResource::collection($faskes),
            'Data faskes berhasil diambil.'
        );
    }

    public function show(Faskes $faskes): JsonResponse
    {
        $faskes->load(['wilayah', 'ambulans']);

        return $this->success(
            new FaskesResource($faskes),
            'Detail faskes berhasil diambil.'
        );
    }

    /**
     * Haversine formula — returns distance in km
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
