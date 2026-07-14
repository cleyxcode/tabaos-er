<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaskesRingkasResource;
use App\Http\Resources\Api\FaskesResource;
use App\Models\Faskes;
use App\Services\HaversineService;
use App\Services\WilayahLokasiService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FaskesController extends Controller
{
    use ApiResponse;

    private const DEFAULT_RADIUS_KM = 75.0;

    public function __construct(
        protected HaversineService $haversine,
        protected WilayahLokasiService $wilayahLokasi,
    ) {}

    /**
     * GET /api/v1/faskes
     *
     * - lat+lng (lokasi saya) → faskes di pulau/kota daerah user saja, urut jarak terdekat
     * - pulau/kota/provinsi → filter wilayah (+ jarak jika ada koordinat)
     * - semua=1 / tanpa filter → semua faskes
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:500',
            'kota' => 'nullable|string|max:100',
            'pulau' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'wilayah_id' => 'nullable|integer|exists:wilayah,id',
            'search' => 'nullable|string|max:100',
            'semua' => 'nullable|boolean',
        ]);

        $provinsi = $request->filled('provinsi') ? $request->string('provinsi')->toString() : null;
        $pulau = $request->filled('pulau') ? $request->string('pulau')->toString() : null;
        $kota = $request->filled('kota') ? $request->string('kota')->toString() : null;
        $semua = $request->boolean('semua');
        $hasWilayahFilter = $provinsi !== null
            || $pulau !== null
            || $kota !== null
            || $request->filled('wilayah_id');
        $hasCoords = $request->filled('lat') && $request->filled('lng');
        $nearbyMode = ! $semua && ! $hasWilayahFilter && $hasCoords;

        $detectedPulau = null;
        $detectedKota = null;

        // Mode lokasi saya: hanya faskes di daerah (pulau) yang sama, bukan kota/pulau lain.
        if ($nearbyMode) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $detectedPulau = $this->wilayahLokasi->deteksiPulau($lat, $lng);
            $detectedKota = $this->wilayahLokasi->deteksiKota($lat, $lng);

            if ($detectedPulau !== null) {
                $pulau = $detectedPulau;
            } elseif ($detectedKota !== null) {
                $kota = $detectedKota;
            }

            $hasWilayahFilter = $pulau !== null || $kota !== null;
        }

        $query = Faskes::query()->with('wilayah');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        if ($hasWilayahFilter) {
            $this->applyWilayahFilter($query, $provinsi, $pulau, $kota);
        }

        if ($nearbyMode || $hasCoords) {
            $query->whereNotNull('latitude')->whereNotNull('longitude');
        }

        /** @var Collection<int, Faskes> $faskes */
        $faskes = $query->get();

        $lat = $hasCoords ? (float) $request->lat : null;
        $lng = $hasCoords ? (float) $request->lng : null;
        $radiusKm = (float) ($request->input('radius_km') ?? self::DEFAULT_RADIUS_KM);
        $hasCustomRadius = $request->filled('radius_km');

        if ($lat !== null && $lng !== null) {
            $faskes = $this->attachJarak($faskes, $lat, $lng);

            if ($nearbyMode) {
                // Radius hanya dikenakan jika client mengirim radius_km (opsi tambahan).
                // Default: semua faskes di pulau/kota yang sama, diurut jarak terdekat.
                if ($hasCustomRadius) {
                    $faskes = $faskes->filter(
                        fn (Faskes $item): bool => (float) $item->jarak_km <= $radiusKm
                    );
                }

                $faskes = $faskes->sortBy('jarak_km')->values();

                $scopeLabel = $detectedPulau
                    ?? $detectedKota
                    ?? $pulau
                    ?? $kota
                    ?? 'daerah Anda';

                return $this->success(
                    FaskesRingkasResource::collection($faskes),
                    "Fasilitas kesehatan terdekat di {$scopeLabel} berhasil diambil.",
                );
            }

            $faskes = $faskes->sortBy('jarak_km')->values();
        } else {
            $faskes = $faskes->sortBy('nama')->values();
        }

        $scopeLabel = match (true) {
            $pulau !== null => "Pulau {$pulau}",
            $kota !== null => $kota,
            $provinsi !== null => "Provinsi {$provinsi}",
            default => null,
        };

        return $this->success(
            FaskesRingkasResource::collection($faskes),
            $scopeLabel
                ? "Fasilitas kesehatan di {$scopeLabel} berhasil diambil."
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

    /**
     * @param  Builder<Faskes>  $query
     */
    private function applyWilayahFilter(
        Builder $query,
        ?string $provinsi,
        ?string $pulau,
        ?string $kota,
    ): void {
        $query->whereHas('wilayah', function ($q) use ($provinsi, $pulau, $kota): void {
            if ($provinsi !== null) {
                $q->where('provinsi', $provinsi);
            }
            if ($pulau !== null) {
                $q->where('pulau', $pulau);
            }
            if ($kota !== null) {
                $q->where('kota', $kota);
            }
        });
    }

    /**
     * @param  Collection<int, Faskes>  $faskes
     * @return Collection<int, Faskes>
     */
    private function attachJarak(Collection $faskes, float $lat, float $lng): Collection
    {
        return $faskes->map(function (Faskes $item) use ($lat, $lng): Faskes {
            $item->jarak_km = $this->haversine->hitungJarak(
                $lat,
                $lng,
                (float) $item->latitude,
                (float) $item->longitude,
            );

            return $item;
        });
    }
}
