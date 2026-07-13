<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaskesRingkasResource;
use App\Http\Resources\Api\FaskesResource;
use App\Models\Faskes;
use App\Services\WilayahLokasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaskesController extends Controller
{
    use ApiResponse;

    public function __construct(protected WilayahLokasiService $wilayahLokasi) {}

    /**
     * GET /api/v1/faskes — fasilitas kesehatan di kota pengguna (nama & alamat saja).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'kota' => 'nullable|string|max:100',
            'wilayah_id' => 'nullable|integer|exists:wilayah,id',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Faskes::with('wilayah');

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->search.'%');
        }

        $kota = $request->filled('kota')
            ? $request->string('kota')->toString()
            : null;

        if ($kota === null && $request->filled('lat') && $request->filled('lng')) {
            $kota = $this->wilayahLokasi->deteksiKota(
                (float) $request->lat,
                (float) $request->lng,
            );
        }

        if ($kota !== null) {
            $query->whereHas('wilayah', fn ($q) => $q->where('kota', $kota));
        }

        $faskes = $query->orderBy('nama')->get();

        return $this->success(
            FaskesRingkasResource::collection($faskes),
            $kota
                ? "Fasilitas kesehatan di {$kota} berhasil diambil."
                : 'Data fasilitas kesehatan berhasil diambil.',
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
}
