<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\PetaRealtimeFilterDTO;
use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\PetugasEmergency;
use App\Models\TitikEvakuasi;
use Illuminate\Support\Collection;

final class PetaRealtimeService
{
    public function __construct(
        private readonly HaversineService $haversine,
    ) {}

    /**
     * @return array{
     *     laporan: list<array<string, mixed>>,
     *     relawan: list<array<string, mixed>>,
     *     faskes: list<array<string, mixed>>,
     *     evakuasi: list<array<string, mixed>>,
     *     petugas: list<array<string, mixed>>,
     *     counts: array<string, int>,
     *     updated_at: string,
     * }
     */
    public function getData(PetaRealtimeFilterDTO $filter): array
    {
        $laporan = $filter->tampilkanLaporan ? $this->getLaporan($filter) : collect();
        $relawan = $filter->tampilkanRelawan ? $this->getRelawan($filter) : collect();
        $faskes = $filter->tampilkanFaskes ? $this->getFaskes($filter) : collect();
        $evakuasi = $filter->tampilkanEvakuasi ? $this->getEvakuasi($filter) : collect();
        $petugas = $filter->tampilkanPetugas ? $this->getPetugas($filter) : collect();

        return [
            'laporan' => $laporan->values()->all(),
            'relawan' => $relawan->values()->all(),
            'faskes' => $faskes->values()->all(),
            'evakuasi' => $evakuasi->values()->all(),
            'petugas' => $petugas->values()->all(),
            'counts' => [
                'laporan' => $laporan->count(),
                'relawan' => $relawan->count(),
                'faskes' => $faskes->count(),
                'evakuasi' => $evakuasi->count(),
                'petugas' => $petugas->count(),
            ],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getLaporan(PetaRealtimeFilterDTO $filter): Collection
    {
        $query = LaporanBencana::query()
            ->with(['wilayah:id,nama', 'relawanDitugaskan.relawan.pengguna:id,name'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->wilayahId !== null) {
            $query->where('wilayah_id', $filter->wilayahId);
        }

        if ($filter->jenisKejadian !== null) {
            $query->where('jenis_kejadian', $filter->jenisKejadian);
        }

        if ($filter->statusLaporan !== null) {
            $query->where('status', $filter->statusLaporan);
        } else {
            $query->where('status', '!=', 'selesai');
        }

        if ($filter->statusPenanganan !== null) {
            $query->where('status_penanganan', $filter->statusPenanganan);
        } else {
            $query->whereIn('status_penanganan', ['belum_ditangani', 'sedang_ditangani']);
        }

        if ($filter->hasRadiusFilter()) {
            $query = $this->haversine->scopeQuery(
                $query,
                (float) $filter->centerLat,
                (float) $filter->centerLng,
                (float) $filter->radiusKm,
            );
        }

        return $query->get()->map(fn (LaporanBencana $item): array => [
            'id' => $item->id,
            'type' => 'laporan',
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'label' => $item->jenis_kejadian,
            'title' => $item->nama_pelapor,
            'subtitle' => $item->alamat_lokasi,
            'status' => $item->status,
            'status_penanganan' => $item->status_penanganan ?? 'belum_ditangani',
            'wilayah' => $item->wilayah?->nama,
            'relawan' => $item->relawanDitugaskan?->relawan?->pengguna?->name,
            'tanggal' => $item->tanggal_kejadian?->format('d M Y H:i'),
            'jarak_km' => isset($item->jarak_km) ? (float) $item->jarak_km : null,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getRelawan(PetaRealtimeFilterDTO $filter): Collection
    {
        $items = AkunRelawan::query()
            ->with('relawan.pengguna:id,name')
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('lokasi_updated_at', '>=', now()->subMinutes($filter->relawanStaleMinutes))
            ->get();

        return $this->applyRadiusFilter($items, $filter, fn (AkunRelawan $akun): array => [
            'id' => $akun->id,
            'type' => 'relawan',
            'latitude' => (float) $akun->latitude,
            'longitude' => (float) $akun->longitude,
            'label' => $akun->relawan?->pengguna?->name ?? 'Relawan',
            'title' => $akun->relawan?->pengguna?->name ?? 'Relawan',
            'subtitle' => $akun->relawan?->organisasi,
            'lokasi_updated_at' => $akun->lokasi_updated_at?->toIso8601String(),
            'keahlian' => $akun->relawan?->keahlian,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getFaskes(PetaRealtimeFilterDTO $filter): Collection
    {
        $query = Faskes::query()
            ->with('wilayah:id,nama')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->wilayahId !== null) {
            $query->where('wilayah_id', $filter->wilayahId);
        }

        $items = $query->get();

        return $this->applyRadiusFilter($items, $filter, fn (Faskes $item): array => [
            'id' => $item->id,
            'type' => 'faskes',
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'label' => $item->nama,
            'title' => $item->nama,
            'subtitle' => $item->alamat,
            'tipe' => $item->tipe,
            'wilayah' => $item->wilayah?->nama,
            'telepon' => $item->nomor_telepon,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getEvakuasi(PetaRealtimeFilterDTO $filter): Collection
    {
        $query = TitikEvakuasi::query()
            ->with('zona.wilayah:id,nama')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->wilayahId !== null) {
            $query->whereHas('zona', fn ($q) => $q->where('wilayah_id', $filter->wilayahId));
        }

        $items = $query->get();

        return $this->applyRadiusFilter($items, $filter, fn (TitikEvakuasi $item): array => [
            'id' => $item->id,
            'type' => 'evakuasi',
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'label' => $item->nama,
            'title' => $item->nama,
            'subtitle' => $item->zona?->nama_zona,
            'kapasitas' => $item->kapasitas,
            'wilayah' => $item->zona?->wilayah?->nama,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getPetugas(PetaRealtimeFilterDTO $filter): Collection
    {
        $items = PetugasEmergency::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return $this->applyRadiusFilter($items, $filter, fn (PetugasEmergency $item): array => [
            'id' => $item->id,
            'type' => 'petugas',
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'label' => $item->nama,
            'title' => $item->nama,
            'subtitle' => $item->kategori,
            'telepon' => $item->nomor_telepon,
            'alamat' => $item->alamat,
        ]);
    }

    /**
     * @template T of AkunRelawan|Faskes|TitikEvakuasi|PetugasEmergency
     *
     * @param  Collection<int, T>  $items
     * @param  callable(T): array<string, mixed>  $mapper
     * @return Collection<int, array<string, mixed>>
     */
    private function applyRadiusFilter(Collection $items, PetaRealtimeFilterDTO $filter, callable $mapper): Collection
    {
        if (! $filter->hasRadiusFilter()) {
            return $items->map($mapper);
        }

        return $items
            ->filter(function ($item) use ($filter): bool {
                $lat = (float) $item->latitude;
                $lng = (float) $item->longitude;

                return $this->haversine->hitungJarak(
                    (float) $filter->centerLat,
                    (float) $filter->centerLng,
                    $lat,
                    $lng,
                ) <= (float) $filter->radiusKm;
            })
            ->map(function ($item) use ($filter, $mapper): array {
                $mapped = $mapper($item);
                $mapped['jarak_km'] = $this->haversine->hitungJarak(
                    (float) $filter->centerLat,
                    (float) $filter->centerLng,
                    (float) $item->latitude,
                    (float) $item->longitude,
                );

                return $mapped;
            });
    }
}
