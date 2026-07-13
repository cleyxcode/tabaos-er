<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WilayahLokasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    use ApiResponse;

    public function __construct(protected WilayahLokasiService $wilayahLokasi) {}

    /**
     * GET /api/v1/wilayah/lokasi — deteksi kota & provinsi dari koordinat GPS.
     */
    public function deteksiLokasi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        $wilayah = $this->wilayahLokasi->cariWilayahTerdekat($lat, $lng);
        $kota = $wilayah?->kota;
        $provinsi = $wilayah?->provinsi;

        $label = match (true) {
            $kota !== null && $provinsi !== null => "{$kota}, {$provinsi}",
            $kota !== null => $kota,
            $provinsi !== null => $provinsi,
            default => null,
        };

        return $this->success([
            'kota' => $kota,
            'provinsi' => $provinsi,
            'label' => $label,
        ], $label ? 'Lokasi berhasil dideteksi.' : 'Wilayah tidak ditemukan untuk koordinat ini.');
    }
}
