<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\DashboardWilayahFilterDTO;
use App\Models\AkunRelawan;
use App\Models\Ambulans;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\NotifikasiAdmin;
use App\Models\Penugasan;
use App\Models\Pengguna;
use App\Models\PetugasEmergency;
use App\Models\Relawan;
use App\Models\TitikEvakuasi;
use App\Models\ZonaRawanBencana;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardStatistikService
{
    /** @var array<string, array<string, mixed>> */
    private array $penangananCache = [];

    /** @var array<string, array<string, mixed>> */
    private array $relawanCache = [];

    /** @var array<string, array<string, mixed>> */
    private array $sumberDayaCache = [];

    /** @var array<string, array<string, int>> */
    private array $korbanCache = [];

    public function __construct(
        private readonly DashboardWilayahFilterDTO $filter = new DashboardWilayahFilterDTO,
        private readonly ?WilayahLokasiService $wilayahLokasi = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function forFilters(?array $filters): self
    {
        return new self(
            DashboardWilayahFilterDTO::fromArray($filters ?? []),
            app(WilayahLokasiService::class),
        );
    }

    /**
     * @return array<string, int>
     */
    public function penangananDarurat(): array
    {
        $key = $this->filter->cacheKey();
        if (isset($this->penangananCache[$key])) {
            return $this->penangananCache[$key];
        }

        $base = $this->laporanQuery();

        $statusCounts = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $penangananCounts = (clone $base)
            ->select('status_penanganan', DB::raw('COUNT(*) as total'))
            ->groupBy('status_penanganan')
            ->pluck('total', 'status_penanganan');

        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        return $this->penangananCache[$key] = [
            'total' => (int) (clone $base)->count(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'diverifikasi' => (int) ($statusCounts['diverifikasi'] ?? 0),
            'ditangani' => (int) ($statusCounts['ditangani'] ?? 0),
            'selesai' => (int) ($statusCounts['selesai'] ?? 0),
            'belum_ditangani' => (int) ($penangananCounts['belum_ditangani'] ?? 0),
            'sedang_ditangani' => (int) ($penangananCounts['sedang_ditangani'] ?? 0),
            'selesai_ditangani' => (int) ($penangananCounts['selesai_ditangani'] ?? 0),
            'hari_ini' => (clone $base)->where('created_at', '>=', $today)->count(),
            'minggu_ini' => (clone $base)->where('created_at', '>=', $weekStart)->count(),
            'butuh_verifikasi' => (clone $base)->where('status', 'pending')->count(),
            'aktif' => (clone $base)->whereNotIn('status', ['selesai'])->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function relawanOperasi(): array
    {
        $key = $this->filter->cacheKey();
        if (isset($this->relawanCache[$key])) {
            return $this->relawanCache[$key];
        }

        $relawanStatus = Relawan::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $akunAktif = AkunRelawan::where('status', 'aktif')->count();
        $akunNonaktif = AkunRelawan::where('status', 'nonaktif')->count();

        $lokasiAktifQuery = AkunRelawan::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('lokasi_updated_at', '>=', now()->subMinutes(30));

        $lokasiAktif = $lokasiAktifQuery->get()->filter(function (AkunRelawan $akun): bool {
            if ($this->filter->isEmpty()) {
                return true;
            }

            return $this->matchesWilayahKoordinat(
                (float) $akun->latitude,
                (float) $akun->longitude,
            );
        })->count();

        $penugasanAktif = Penugasan::query()
            ->whereIn('status', ['ditugaskan', 'dalam_perjalanan']);
        $this->applyPenugasanWilayahFilter($penugasanAktif);

        $penugasanSelesai = Penugasan::query()->where('status', 'selesai');
        $this->applyPenugasanWilayahFilter($penugasanSelesai);

        $laporanDiklaim = $this->laporanQuery()
            ->whereNotNull('akun_relawan_ditugaskan');

        return $this->relawanCache[$key] = [
            'total' => (int) Relawan::count(),
            'disetujui' => (int) ($relawanStatus['disetujui'] ?? 0),
            'pending' => (int) ($relawanStatus['pending'] ?? 0),
            'ditolak' => (int) ($relawanStatus['ditolak'] ?? 0),
            'akun_aktif' => $akunAktif,
            'akun_nonaktif' => $akunNonaktif,
            'lokasi_aktif' => $lokasiAktif,
            'penugasan_aktif' => $penugasanAktif->count(),
            'penugasan_selesai' => $penugasanSelesai->count(),
            'laporan_diklaim' => $laporanDiklaim->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function sumberDaya(): array
    {
        $key = $this->filter->cacheKey();
        if (isset($this->sumberDayaCache[$key])) {
            return $this->sumberDayaCache[$key];
        }

        $faskesQuery = Faskes::query();
        $this->filter->applyToQuery($faskesQuery);

        $faskesTipe = (clone $faskesQuery)
            ->select('tipe', DB::raw('COUNT(*) as total'))
            ->groupBy('tipe')
            ->pluck('total', 'tipe');

        $zonaQuery = ZonaRawanBencana::query();
        $this->filter->applyToQuery($zonaQuery);

        $zonaRisiko = (clone $zonaQuery)
            ->select('tingkat_risiko', DB::raw('COUNT(*) as total'))
            ->groupBy('tingkat_risiko')
            ->pluck('total', 'tingkat_risiko');

        $ambulansQuery = Ambulans::query();
        if (! $this->filter->isEmpty()) {
            $ambulansQuery->whereHas('faskes', function (Builder $q): void {
                $this->filter->applyToQuery($q);
            });
        }

        $titikQuery = TitikEvakuasi::query();
        if (! $this->filter->isEmpty()) {
            $titikQuery->whereHas('zona', function (Builder $q): void {
                $this->filter->applyToQuery($q);
            });
        }

        $petugasAktif = PetugasEmergency::where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->filter(fn (PetugasEmergency $item) => $this->matchesWilayahKoordinat(
                (float) $item->latitude,
                (float) $item->longitude,
            ))
            ->count();

        $petugasTotal = $this->filter->isEmpty()
            ? PetugasEmergency::count()
            : PetugasEmergency::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->filter(fn (PetugasEmergency $item) => $this->matchesWilayahKoordinat(
                    (float) $item->latitude,
                    (float) $item->longitude,
                ))
                ->count();

        return $this->sumberDayaCache[$key] = [
            'pengguna' => Pengguna::count(),
            'faskes' => (clone $faskesQuery)->count(),
            'faskes_rs' => (int) ($faskesTipe['rumah_sakit'] ?? 0),
            'faskes_puskesmas' => (int) ($faskesTipe['puskesmas'] ?? 0),
            'faskes_apotek' => (int) ($faskesTipe['apotek'] ?? 0),
            'ambulans_total' => (clone $ambulansQuery)->count(),
            'ambulans_tersedia' => (clone $ambulansQuery)->where('status', 'tersedia')->count(),
            'petugas_aktif' => $petugasAktif,
            'petugas_total' => $petugasTotal,
            'titik_evakuasi' => $titikQuery->count(),
            'zona_rawan' => (clone $zonaQuery)->count(),
            'zona_tinggi' => (int) ($zonaRisiko['tinggi'] ?? 0),
            'zona_sedang' => (int) ($zonaRisiko['sedang'] ?? 0),
            'zona_rendah' => (int) ($zonaRisiko['rendah'] ?? 0),
            'notifikasi_admin' => NotifikasiAdmin::count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function korbanJiwa(): array
    {
        $key = $this->filter->cacheKey();
        if (isset($this->korbanCache[$key])) {
            return $this->korbanCache[$key];
        }

        $totals = $this->laporanQuery()
            ->selectRaw('
                COALESCE(SUM(meninggal_jumlah), 0) as meninggal,
                COALESCE(SUM(hilang_jumlah), 0) as hilang,
                COALESCE(SUM(luka_berat_jumlah), 0) as luka_berat,
                COALESCE(SUM(luka_ringan_jumlah), 0) as luka_ringan
            ')
            ->first();

        $laporanDenganKorban = $this->laporanQuery()
            ->where(function ($query): void {
                $query->where('meninggal_jumlah', '>', 0)
                    ->orWhere('hilang_jumlah', '>', 0)
                    ->orWhere('luka_berat_jumlah', '>', 0)
                    ->orWhere('luka_ringan_jumlah', '>', 0);
            })
            ->count();

        return $this->korbanCache[$key] = [
            'meninggal' => (int) ($totals->meninggal ?? 0),
            'hilang' => (int) ($totals->hilang ?? 0),
            'luka_berat' => (int) ($totals->luka_berat ?? 0),
            'luka_ringan' => (int) ($totals->luka_ringan ?? 0),
            'laporan_berkorban' => $laporanDenganKorban,
            'total_korban' => (int) (($totals->meninggal ?? 0)
                + ($totals->hilang ?? 0)
                + ($totals->luka_berat ?? 0)
                + ($totals->luka_ringan ?? 0)),
        ];
    }

    /**
     * @return list<int>
     */
    public function sparklineHarian(int $days = 7, ?callable $constraint = null): array
    {
        return $this->dailyCounts($days, $constraint)->values()->all();
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function trendLaporan(int $days = 30): array
    {
        $counts = $this->dailyCounts($days);

        return [
            'labels' => $counts->keys()->all(),
            'data' => $counts->values()->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function laporanPerJenisKejadian(): array
    {
        $items = $this->laporanQuery()
            ->select('jenis_kejadian', DB::raw('COUNT(*) as total'))
            ->groupBy('jenis_kejadian')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $items->pluck('jenis_kejadian')->all(),
            'data' => $items->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function laporanPerStatus(): array
    {
        $map = [
            'pending' => 'Pending',
            'diverifikasi' => 'Diverifikasi',
            'ditangani' => 'Ditangani',
            'selesai' => 'Selesai',
        ];

        $counts = $this->laporanQuery()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];

        foreach ($map as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function laporanPerPenanganan(): array
    {
        $map = [
            'belum_ditangani' => 'Belum Ditangani',
            'sedang_ditangani' => 'Sedang Ditangani',
            'selesai_ditangani' => 'Selesai Ditangani',
        ];

        $counts = $this->laporanQuery()
            ->select('status_penanganan', DB::raw('COUNT(*) as total'))
            ->groupBy('status_penanganan')
            ->pluck('total', 'status_penanganan');

        $labels = [];
        $data = [];

        foreach ($map as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    /**
     * @return Builder<LaporanBencana>
     */
    private function laporanQuery(): Builder
    {
        $query = LaporanBencana::query();
        $this->filter->applyToQuery($query);

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyPenugasanWilayahFilter(Builder $query): void
    {
        if ($this->filter->isEmpty()) {
            return;
        }

        $query->whereHas('laporan', function (Builder $q): void {
            $this->filter->applyToQuery($q);
        });
    }

    private function matchesWilayahKoordinat(float $lat, float $lng): bool
    {
        if ($this->filter->isEmpty()) {
            return true;
        }

        $lokasi = $this->wilayahLokasi ?? app(WilayahLokasiService::class);
        $wilayah = $lokasi->cariWilayahTerdekat($lat, $lng);

        if ($wilayah === null) {
            return false;
        }

        if ($this->filter->wilayahId !== null && $wilayah->id !== $this->filter->wilayahId) {
            return false;
        }

        if ($this->filter->provinsi !== null && $wilayah->provinsi !== $this->filter->provinsi) {
            return false;
        }

        if ($this->filter->pulau !== null && $wilayah->pulau !== $this->filter->pulau) {
            return false;
        }

        if ($this->filter->kota !== null && $wilayah->kota !== $this->filter->kota) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<string, int>
     */
    private function dailyCounts(int $days, ?callable $constraint = null): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $query = $this->laporanQuery()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal');

        if ($constraint !== null) {
            $constraint($query);
        }

        $existing = $query->pluck('total', 'tanggal');

        $result = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $key = $date->toDateString();
            $result->put(
                $date->locale('id')->translatedFormat('d M'),
                (int) ($existing[$key] ?? 0),
            );
        }

        return $result;
    }
}
