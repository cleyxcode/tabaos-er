<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\RelawanNotifikasi;
use App\Models\Wilayah;
use App\Services\FcmV1Client;
use App\Services\HaversineService;
use App\Services\NotifikasiService;
use App\Services\RelawanPenugasanService;
use App\Services\WilayahLokasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testHanyaSatuRelawanTerdekatYangMenerimaNotifikasi(): void
    {
        $haversine = new HaversineService;
        $wilayahLokasi = new WilayahLokasiService($haversine);
        $penugasan = new RelawanPenugasanService($haversine, $wilayahLokasi);
        $service = new NotifikasiService($penugasan, new FcmV1Client);

        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $dekat = $this->buatAkunRelawan('Dekat', -3.6960, 128.1805, 'fcm-dekat');
        $this->buatAkunRelawan('Sedang', -3.7100, 128.2000, 'fcm-sedang');
        $this->buatAkunRelawan('Tual', -5.6425, 132.7485, 'fcm-tual');

        $laporan = LaporanBencana::withoutEvents(fn () => LaporanBencana::create([
            'wilayah_id' => Wilayah::first()->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test banjir',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
        ]));

        $service->kirimKeRelawanTerdekat($laporan);

        $laporan->refresh();
        $this->assertSame($dekat->id, $laporan->akun_relawan_ditugaskan);
        $this->assertSame(1, RelawanNotifikasi::count());
        $this->assertSame($dekat->id, RelawanNotifikasi::first()->akun_relawan_id);
    }

    private function buatAkunRelawan(string $nama, float $lat, float $lng, string $fcmToken): AkunRelawan
    {
        $pengguna = Pengguna::create([
            'name' => $nama,
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => strtolower($nama).'@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        return AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.'.strtolower($nama).'@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
            'latitude' => $lat,
            'longitude' => $lng,
            'lokasi_updated_at' => now(),
            'fcm_token' => $fcmToken,
        ]);
    }
}
