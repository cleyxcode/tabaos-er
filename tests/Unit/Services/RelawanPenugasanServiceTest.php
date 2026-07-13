<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\Wilayah;
use App\Services\HaversineService;
use App\Services\RelawanPenugasanService;
use App\Services\WilayahLokasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelawanPenugasanServiceTest extends TestCase
{
    use RefreshDatabase;

    private RelawanPenugasanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $haversine = new HaversineService;
        $this->service = new RelawanPenugasanService(
            $haversine,
            new WilayahLokasiService($haversine),
        );
    }

    public function testMemilihSatuRelawanTerdekatDalamProvinsiSama(): void
    {
        $this->seedWilayahMaluku();

        $ambonDekat = $this->buatAkunRelawan('Dekat Ambon', -3.6960, 128.1805);
        $this->buatAkunRelawan('Jauh Ambon', -3.7500, 128.2500);
        $this->buatAkunRelawan('Tual', -5.6425, 132.7485);

        $terdekat = $this->service->cariRelawanTerdekat(-3.695845, 128.181011, 'Maluku');

        $this->assertNotNull($terdekat);
        $this->assertSame($ambonDekat->id, $terdekat->id);
    }

    public function testTidakMemilihRelawanDariPulauLainMeskiLebihDekatSecaraGeografis(): void
    {
        $this->seedWilayahMaluku();

        $this->buatAkunRelawan('Tual', -5.6425, 132.7485);
        $ambon = $this->buatAkunRelawan('Ambon', -3.6960, 128.1805);

        $terdekat = $this->service->cariRelawanTerdekat(-3.695845, 128.181011, 'Maluku');

        $this->assertSame($ambon->id, $terdekat->id);
    }

    public function testTugaskanRelawanTerdekatMenetapkanAkunRelawanDitugaskan(): void
    {
        $this->seedWilayahMaluku();

        $akun = $this->buatAkunRelawan('Ambon', -3.6960, 128.1805);
        $this->buatAkunRelawan('Tual', -5.6425, 132.7485);

        $wilayah = Wilayah::where('kota', 'Kota Ambon')->first();

        $laporan = LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
        ]);

        $hasil = $this->service->tugaskanRelawanTerdekat($laporan);

        $this->assertNotNull($hasil);
        $this->assertSame($akun->id, $hasil->id);
        $laporan->refresh();
        $this->assertSame($akun->id, $laporan->akun_relawan_ditugaskan);
    }

    public function testRelawanBerhakAksesHanyaLaporanYangDitugaskan(): void
    {
        $this->seedWilayahMaluku();

        $akunA = $this->buatAkunRelawan('A', -3.6960, 128.1805);
        $akunB = $this->buatAkunRelawan('B', -3.7500, 128.2500);

        $laporan = LaporanBencana::create([
            'wilayah_id' => Wilayah::first()->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
            'akun_relawan_ditugaskan' => $akunA->id,
        ]);

        $this->assertTrue($this->service->relawanBerhakAksesLaporan($akunA, $laporan));
        $this->assertFalse($this->service->relawanBerhakAksesLaporan($akunB, $laporan));
    }

    private function seedWilayahMaluku(): void
    {
        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        Wilayah::create([
            'nama' => 'Tual',
            'kecamatan' => 'Tual Kota',
            'kota' => 'Tual',
            'provinsi' => 'Maluku',
            'latitude' => -5.6417,
            'longitude' => 132.7472,
        ]);
    }

    private function buatAkunRelawan(string $nama, float $lat, float $lng): AkunRelawan
    {
        $pengguna = Pengguna::create([
            'name' => $nama,
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => strtolower(str_replace(' ', '.', $nama)).'@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        return AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.'.strtolower(str_replace(' ', '.', $nama)).'@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
            'latitude' => $lat,
            'longitude' => $lng,
            'lokasi_updated_at' => now(),
        ]);
    }
}
