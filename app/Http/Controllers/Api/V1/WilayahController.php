<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use App\Services\WilayahLokasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    use ApiResponse;

    public function __construct(protected WilayahLokasiService $wilayahLokasi) {}

    /**
     * GET /api/v1/wilayah/lokasi — deteksi kota, pulau & provinsi dari koordinat GPS.
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
        $pulau = $wilayah?->pulau;
        $provinsi = $wilayah?->provinsi;

        $label = match (true) {
            $pulau !== null && $provinsi !== null => "{$pulau}, {$provinsi}",
            $kota !== null && $provinsi !== null => "{$kota}, {$provinsi}",
            $pulau !== null => $pulau,
            $kota !== null => $kota,
            $provinsi !== null => $provinsi,
            default => null,
        };

        return $this->success([
            'kota' => $kota,
            'pulau' => $pulau,
            'provinsi' => $provinsi,
            'wilayah_id' => $wilayah?->id,
            'label' => $label,
        ], $label ? 'Lokasi berhasil dideteksi.' : 'Wilayah tidak ditemukan untuk koordinat ini.');
    }

    /**
     * GET /api/v1/wilayah/opsi — daftar opsi filter wilayah (provinsi / pulau / kota).
     */
    public function opsiFilter(Request $request): JsonResponse
    {
        $request->validate([
            'provinsi' => 'nullable|string|max:100',
            'pulau' => 'nullable|string|max:100',
        ]);

        $provinsi = $request->string('provinsi')->toString() ?: null;
        $pulau = $request->string('pulau')->toString() ?: null;

        $provinsiList = Wilayah::query()
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi')
            ->values()
            ->all();

        $pulauList = Wilayah::query()
            ->when($provinsi, fn ($q) => $q->where('provinsi', $provinsi))
            ->whereNotNull('pulau')
            ->where('pulau', '!=', '')
            ->distinct()
            ->orderBy('pulau')
            ->pluck('pulau')
            ->values()
            ->all();

        $kotaList = Wilayah::query()
            ->when($provinsi, fn ($q) => $q->where('provinsi', $provinsi))
            ->when($pulau, fn ($q) => $q->where('pulau', $pulau))
            ->whereNotNull('kota')
            ->where('kota', '!=', '')
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota')
            ->values()
            ->all();

        return $this->success([
            'provinsi' => $provinsiList,
            'pulau' => $pulauList,
            'kota' => $kotaList,
        ], 'Opsi filter wilayah berhasil diambil.');
    }
}
