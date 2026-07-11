<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\LaporanBencana;
use App\Models\Relawan;
use App\Models\Wilayah;
use App\Services\DashboardStatistikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardStatistikServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardStatistikService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardStatistikService;
    }

    public function testPenangananDaruratMenghitungStatusDanPenanganan(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'A',
            'nomor_kontak' => '08111111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.69,
            'longitude' => 128.18,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'B',
            'nomor_kontak' => '08222222222',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.70,
            'longitude' => 128.19,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
            'meninggal_jumlah' => 1,
        ]);

        $stats = $this->service->penangananDarurat();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['pending']);
        $this->assertSame(1, $stats['sedang_ditangani']);
        $this->assertSame(2, $stats['hari_ini']);
    }

    public function testKorbanJiwaMenjumlahkanAgregat(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'A',
            'nomor_kontak' => '08111111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.69,
            'longitude' => 128.18,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'meninggal_jumlah' => 2,
            'luka_ringan_jumlah' => 3,
        ]);

        $stats = $this->service->korbanJiwa();

        $this->assertSame(2, $stats['meninggal']);
        $this->assertSame(3, $stats['luka_ringan']);
        $this->assertSame(5, $stats['total_korban']);
        $this->assertSame(1, $stats['laporan_berkorban']);
    }

    public function testTrendLaporanMengembalikanLabelDanData(): void
    {
        $trend = $this->service->trendLaporan(7);

        $this->assertCount(7, $trend['labels']);
        $this->assertCount(7, $trend['data']);
    }

    public function testRelawanOperasiMenghitungStatusRelawan(): void
    {
        Relawan::create([
            'nik' => '1234567890123456',
            'alamat' => 'Ambon',
            'keahlian' => 'Medis',
            'status' => 'disetujui',
        ]);

        Relawan::create([
            'nik' => '1234567890123457',
            'alamat' => 'Ambon',
            'keahlian' => 'Logistik',
            'status' => 'pending',
        ]);

        $stats = $this->service->relawanOperasi();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['disetujui']);
        $this->assertSame(1, $stats['pending']);
    }
}
