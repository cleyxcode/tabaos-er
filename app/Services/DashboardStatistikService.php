<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkunFaskes;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardStatistikService
{
    /** @var array<string, mixed>|null */
    private ?array $penangananCache = null;

    /** @var array<string, mixed>|null */
    private ?array $relawanCache = null;

    /** @var array<string, mixed>|null */
    private ?array $sumberDayaCache = null;

    /** @var array<string, int>|null */
    private ?array $korbanCache = null;

    /**
     * @return array<string, int>
     */
    public function penangananDarurat(): array
    {
        if ($this->penangananCache !== null) {
            return $this->penangananCache;
        }

        $statusCounts = LaporanBencana::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $penangananCounts = LaporanBencana::query()
            ->select('status_penanganan', DB::raw('COUNT(*) as total'))
            ->groupBy('status_penanganan')
            ->pluck('total', 'status_penanganan');

        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        return $this->penangananCache = [
            'total' => (int) LaporanBencana::count(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'diverifikasi' => (int) ($statusCounts['diverifikasi'] ?? 0),
            'ditangani' => (int) ($statusCounts['ditangani'] ?? 0),
            'selesai' => (int) ($statusCounts['selesai'] ?? 0),
            'belum_ditangani' => (int) ($penangananCounts['belum_ditangani'] ?? 0),
            'sedang_ditangani' => (int) ($penangananCounts['sedang_ditangani'] ?? 0),
            'selesai_ditangani' => (int) ($penangananCounts['selesai_ditangani'] ?? 0),
            'hari_ini' => LaporanBencana::where('created_at', '>=', $today)->count(),
            'minggu_ini' => LaporanBencana::where('created_at', '>=', $weekStart)->count(),
            'butuh_verifikasi' => LaporanBencana::where('status', 'pending')->count(),
            'aktif' => LaporanBencana::whereNotIn('status', ['selesai'])->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function relawanOperasi(): array
    {
        if ($this->relawanCache !== null) {
            return $this->relawanCache;
        }

        $relawanStatus = Relawan::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->relawanCache = [
            'total' => (int) Relawan::count(),
            'disetujui' => (int) ($relawanStatus['disetujui'] ?? 0),
            'pending' => (int) ($relawanStatus['pending'] ?? 0),
            'ditolak' => (int) ($relawanStatus['ditolak'] ?? 0),
            'akun_aktif' => AkunRelawan::where('status', 'aktif')->count(),
            'akun_nonaktif' => AkunRelawan::where('status', 'nonaktif')->count(),
            'lokasi_aktif' => AkunRelawan::query()
                ->where('status', 'aktif')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('lokasi_updated_at', '>=', now()->subMinutes(30))
                ->count(),
            'penugasan_aktif' => Penugasan::query()
                ->whereIn('status', ['ditugaskan', 'dalam_perjalanan'])
                ->count(),
            'penugasan_selesai' => Penugasan::where('status', 'selesai')->count(),
            'laporan_diklaim' => LaporanBencana::whereNotNull('akun_relawan_ditugaskan')->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function sumberDaya(): array
    {
        if ($this->sumberDayaCache !== null) {
            return $this->sumberDayaCache;
        }

        $faskesTipe = Faskes::query()
            ->select('tipe', DB::raw('COUNT(*) as total'))
            ->groupBy('tipe')
            ->pluck('total', 'tipe');

        $zonaRisiko = ZonaRawanBencana::query()
            ->select('tingkat_risiko', DB::raw('COUNT(*) as total'))
            ->groupBy('tingkat_risiko')
            ->pluck('total', 'tingkat_risiko');

        return $this->sumberDayaCache = [
            'pengguna' => Pengguna::count(),
            'faskes' => Faskes::count(),
            'faskes_rs' => (int) ($faskesTipe['rumah_sakit'] ?? 0),
            'faskes_puskesmas' => (int) ($faskesTipe['puskesmas'] ?? 0),
            'faskes_apotek' => (int) ($faskesTipe['apotek'] ?? 0),
            'akun_faskes_aktif' => AkunFaskes::where('status', 'aktif')->count(),
            'ambulans_total' => Ambulans::count(),
            'ambulans_tersedia' => Ambulans::where('status', 'tersedia')->count(),
            'petugas_aktif' => PetugasEmergency::where('status', 'aktif')->count(),
            'petugas_total' => PetugasEmergency::count(),
            'titik_evakuasi' => TitikEvakuasi::count(),
            'zona_rawan' => ZonaRawanBencana::count(),
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
        if ($this->korbanCache !== null) {
            return $this->korbanCache;
        }

        $totals = LaporanBencana::query()
            ->selectRaw('
                COALESCE(SUM(meninggal_jumlah), 0) as meninggal,
                COALESCE(SUM(hilang_jumlah), 0) as hilang,
                COALESCE(SUM(luka_berat_jumlah), 0) as luka_berat,
                COALESCE(SUM(luka_ringan_jumlah), 0) as luka_ringan
            ')
            ->first();

        $laporanDenganKorban = LaporanBencana::query()
            ->where(function ($query): void {
                $query->where('meninggal_jumlah', '>', 0)
                    ->orWhere('hilang_jumlah', '>', 0)
                    ->orWhere('luka_berat_jumlah', '>', 0)
                    ->orWhere('luka_ringan_jumlah', '>', 0);
            })
            ->count();

        return $this->korbanCache = [
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
        $items = LaporanBencana::query()
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

        $counts = LaporanBencana::query()
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

        $counts = LaporanBencana::query()
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
     * @return Collection<string, int>
     */
    private function dailyCounts(int $days, ?callable $constraint = null): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $query = LaporanBencana::query()
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
