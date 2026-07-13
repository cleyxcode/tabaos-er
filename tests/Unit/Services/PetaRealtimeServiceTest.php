<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\PetaRealtimeFilterDTO;
use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\PetugasEmergency;
use App\Models\Relawan;
use App\Models\TitikEvakuasi;
use App\Models\Wilayah;
use App\Models\ZonaRawanBencana;
use App\Services\HaversineService;
use App\Services\PetaRealtimeService;
use App\Services\WilayahLokasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PetaRealtimeServiceTest extends TestCase
{
    use RefreshDatabase;

    private PetaRealtimeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PetaRealtimeService(
            new HaversineService,
            app(WilayahLokasiService::class),
        );
    }

    public function testGetDataReturnsAllMarkerTypes(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Budi',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test laporan',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
        ]);

        Faskes::create([
            'wilayah_id' => $wilayah->id,
            'nama' => 'RSUD Ambon',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Ambon',
            'latitude' => -3.6900,
            'longitude' => 128.1850,
        ]);

        $zona = ZonaRawanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_zona' => 'Zona A',
            'tingkat_risiko' => 'tinggi',
            'polygon' => [],
        ]);

        TitikEvakuasi::create([
            'zona_id' => $zona->id,
            'nama' => 'Lapangan Merdeka',
            'latitude' => -3.6960,
            'longitude' => 128.1805,
            'kapasitas' => 500,
        ]);

        PetugasEmergency::create([
            'nama' => 'Petugas SAR',
            'kategori' => 'sar',
            'nomor_telepon' => '0811111111',
            'latitude' => -3.6920,
            'longitude' => 128.1820,
            'status' => 'aktif',
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Relawan Test',
            'phone' => '081299988877',
            'email' => 'relawan@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'nik' => '1234567890123456',
            'alamat' => 'Ambon',
            'keahlian' => 'P3K',
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.relawan@test.com',
            'password' => bcrypt('secret'),
            'latitude' => -3.6940,
            'longitude' => 128.1830,
            'lokasi_updated_at' => now(),
            'status' => 'aktif',
        ]);

        $result = $this->service->getData(new PetaRealtimeFilterDTO);

        $this->assertSame(1, $result['counts']['laporan']);
        $this->assertSame(1, $result['counts']['relawan']);
        $this->assertSame(1, $result['counts']['faskes']);
        $this->assertSame(1, $result['counts']['evakuasi']);
        $this->assertSame(1, $result['counts']['petugas']);
        $this->assertArrayHasKey('updated_at', $result);
    }

    public function testFilterByWilayahAndLayerToggle(): void
    {
        $wilayahA = Wilayah::create(['nama' => 'A', 'kecamatan' => 'A', 'kota' => 'Ambon']);
        $wilayahB = Wilayah::create(['nama' => 'B', 'kecamatan' => 'B', 'kota' => 'Ambon']);

        LaporanBencana::create([
            'wilayah_id' => $wilayahA->id,
            'nama_pelapor' => 'A',
            'nomor_kontak' => '0811111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.69,
            'longitude' => 128.18,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'A',
            'status' => 'pending',
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayahB->id,
            'nama_pelapor' => 'B',
            'nomor_kontak' => '0822222222',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.70,
            'longitude' => 128.19,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'B',
            'status' => 'pending',
        ]);

        $filter = new PetaRealtimeFilterDTO(
            wilayahId: $wilayahA->id,
            tampilkanRelawan: false,
            tampilkanFaskes: false,
            tampilkanEvakuasi: false,
            tampilkanPetugas: false,
        );

        $result = $this->service->getData($filter);

        $this->assertSame(1, $result['counts']['laporan']);
        $this->assertSame('Banjir', $result['laporan'][0]['label']);
        $this->assertSame(0, $result['counts']['relawan']);
    }

    public function testRadiusFilterLimitsResults(): void
    {
        LaporanBencana::create([
            'nama_pelapor' => 'Dekat',
            'nomor_kontak' => '0811111111',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Dekat',
            'status' => 'pending',
        ]);

        LaporanBencana::create([
            'nama_pelapor' => 'Jauh',
            'nomor_kontak' => '0822222222',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -4.500000,
            'longitude' => 129.000000,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Jauh',
            'status' => 'pending',
        ]);

        $filter = new PetaRealtimeFilterDTO(
            centerLat: -3.695845,
            centerLng: 128.181011,
            radiusKm: 5.0,
            tampilkanRelawan: false,
            tampilkanFaskes: false,
            tampilkanEvakuasi: false,
            tampilkanPetugas: false,
        );

        $result = $this->service->getData($filter);

        $this->assertSame(1, $result['counts']['laporan']);
        $this->assertSame('Dekat', $result['laporan'][0]['title']);
    }

    public function testResolvedLaporanExcludedFromMapByDefault(): void
    {
        LaporanBencana::create([
            'nama_pelapor' => 'Aktif',
            'nomor_kontak' => '0811111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Aktif',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
        ]);

        LaporanBencana::create([
            'nama_pelapor' => 'Selesai Penanganan',
            'nomor_kontak' => '0822222222',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.696845,
            'longitude' => 128.182011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Selesai penanganan',
            'status' => 'ditangani',
            'status_penanganan' => 'selesai_ditangani',
        ]);

        LaporanBencana::create([
            'nama_pelapor' => 'Selesai Status',
            'nomor_kontak' => '0833333333',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -3.697845,
            'longitude' => 128.183011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Selesai status',
            'status' => 'selesai',
            'status_penanganan' => 'sedang_ditangani',
        ]);

        $filter = new PetaRealtimeFilterDTO(
            tampilkanRelawan: false,
            tampilkanFaskes: false,
            tampilkanEvakuasi: false,
            tampilkanPetugas: false,
        );

        $result = $this->service->getData($filter);

        $this->assertSame(1, $result['counts']['laporan']);
        $this->assertSame('Aktif', $result['laporan'][0]['title']);
    }
}
