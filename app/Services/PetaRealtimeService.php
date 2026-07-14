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
        private readonly WilayahLokasiService $wilayahLokasi,
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
            ->with(['wilayah:id,nama,kota,pulau,provinsi', 'relawanDitugaskan.relawan.pengguna:id,name'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $this->applyWilayahQuery($query, $filter, 'wilayah');

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

        $items = $query->get();

        return $this->applyRadiusFilter($items, $filter, fn (LaporanBencana $item): array => [
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
            'kota' => $item->wilayah?->kota,
            'pulau' => $item->wilayah?->pulau,
            'provinsi' => $item->wilayah?->provinsi,
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
            ->with('relawan.pengguna:id,name,phone')
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('lokasi_updated_at', '>=', now()->subMinutes($filter->relawanStaleMinutes))
            ->get()
            ->filter(fn (AkunRelawan $akun) => $this->matchesWilayahKoordinat(
                $filter,
                (float) $akun->latitude,
                (float) $akun->longitude,
            ));

        return $this->applyRadiusFilter($items, $filter, function (AkunRelawan $akun) use ($filter): array {
            $nama = $akun->relawan?->pengguna?->name ?? 'Relawan';
            $organisasi = $akun->relawan?->organisasi;
            $wilayah = $this->wilayahLokasi->cariWilayahTerdekat(
                (float) $akun->latitude,
                (float) $akun->longitude,
            );

            return [
                'id' => $akun->id,
                'type' => 'relawan',
                'latitude' => (float) $akun->latitude,
                'longitude' => (float) $akun->longitude,
                'label' => $nama,
                'title' => $nama,
                'subtitle' => filled($organisasi) ? $organisasi : 'Relawan mandiri',
                'lokasi_updated_at' => $akun->lokasi_updated_at?->toIso8601String(),
                'keahlian' => $akun->relawan?->keahlian,
                'organisasi' => $organisasi,
                'email' => $akun->email,
                'telepon' => $akun->relawan?->pengguna?->phone,
                'kota' => $wilayah?->kota,
                'pulau' => $wilayah?->pulau,
                'provinsi' => $wilayah?->provinsi,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getFaskes(PetaRealtimeFilterDTO $filter): Collection
    {
        $query = Faskes::query()
            ->with('wilayah:id,nama,kota,pulau,provinsi')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $this->applyWilayahQuery($query, $filter);

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
            'tipe_label' => match ($item->tipe) {
                'rumah_sakit' => 'Rumah Sakit',
                'puskesmas' => 'Puskesmas',
                'apotek' => 'Apotek',
                default => ucfirst(str_replace('_', ' ', (string) $item->tipe)),
            },
            'alamat' => $item->alamat,
            'wilayah' => $item->wilayah?->nama,
            'kota' => $item->wilayah?->kota,
            'pulau' => $item->wilayah?->pulau,
            'provinsi' => $item->wilayah?->provinsi,
            'telepon' => $item->nomor_telepon,
            'jam_operasional' => $item->jam_operasional,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getEvakuasi(PetaRealtimeFilterDTO $filter): Collection
    {
        $query = TitikEvakuasi::query()
            ->with('zona.wilayah:id,nama,kota,pulau,provinsi')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->wilayahId !== null) {
            $query->whereHas('zona', fn ($q) => $q->where('wilayah_id', $filter->wilayahId));
        } elseif ($filter->kota !== null || $filter->pulau !== null || $filter->provinsi !== null) {
            $query->whereHas('zona.wilayah', function ($q) use ($filter): void {
                if ($filter->provinsi !== null) {
                    $q->where('provinsi', $filter->provinsi);
                }
                if ($filter->pulau !== null) {
                    $q->where('pulau', $filter->pulau);
                }
                if ($filter->kota !== null) {
                    $q->where('kota', $filter->kota);
                }
            });
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
            'kota' => $item->zona?->wilayah?->kota,
            'pulau' => $item->zona?->wilayah?->pulau,
            'provinsi' => $item->zona?->wilayah?->provinsi,
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
            ->get()
            ->filter(fn (PetugasEmergency $item) => $this->matchesWilayahKoordinat(
                $filter,
                (float) $item->latitude,
                (float) $item->longitude,
            ));

        return $this->applyRadiusFilter($items, $filter, function (PetugasEmergency $item): array {
            $wilayah = $this->wilayahLokasi->cariWilayahTerdekat(
                (float) $item->latitude,
                (float) $item->longitude,
            );

            return [
                'id' => $item->id,
                'type' => 'petugas',
                'latitude' => (float) $item->latitude,
                'longitude' => (float) $item->longitude,
                'label' => $item->nama,
                'title' => $item->nama,
                'subtitle' => $item->kategori,
                'telepon' => $item->nomor_telepon,
                'alamat' => $item->alamat,
                'kota' => $wilayah?->kota,
                'pulau' => $wilayah?->pulau,
                'provinsi' => $wilayah?->provinsi,
            ];
        });
    }

    private function applyWilayahQuery($query, PetaRealtimeFilterDTO $filter, ?string $relation = null): void
    {
        if ($filter->wilayahId !== null) {
            if ($relation === 'wilayah') {
                $query->where('wilayah_id', $filter->wilayahId);
            } elseif ($relation !== null) {
                $query->whereHas($relation, fn ($q) => $q->where('id', $filter->wilayahId));
            } else {
                $query->where('wilayah_id', $filter->wilayahId);
            }

            return;
        }

        $target = $relation ?? 'wilayah';

        if ($filter->provinsi !== null) {
            $query->whereHas($target, fn ($q) => $q->where('provinsi', $filter->provinsi));
        }

        if ($filter->pulau !== null) {
            $query->whereHas($target, fn ($q) => $q->where('pulau', $filter->pulau));
        }

        if ($filter->kota !== null) {
            $query->whereHas($target, fn ($q) => $q->where('kota', $filter->kota));
        }
    }

    private function matchesWilayahKoordinat(PetaRealtimeFilterDTO $filter, float $lat, float $lng): bool
    {
        if ($filter->wilayahId !== null) {
            $wilayah = $this->wilayahLokasi->cariWilayahTerdekat($lat, $lng);

            return $wilayah?->id === $filter->wilayahId;
        }

        if ($filter->provinsi === null && $filter->pulau === null && $filter->kota === null) {
            return true;
        }

        $wilayah = $this->wilayahLokasi->cariWilayahTerdekat($lat, $lng);

        if ($wilayah === null) {
            return false;
        }

        if ($filter->provinsi !== null && $wilayah->provinsi !== $filter->provinsi) {
            return false;
        }

        if ($filter->pulau !== null && $wilayah->pulau !== $filter->pulau) {
            return false;
        }

        if ($filter->kota !== null && $wilayah->kota !== $filter->kota) {
            return false;
        }

        return true;
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
