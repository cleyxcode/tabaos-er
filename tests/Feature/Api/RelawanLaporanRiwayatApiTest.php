<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RelawanLaporanRiwayatApiTest extends TestCase
{
    use RefreshDatabase;

    public function testRiwayatMenampilkanSelesaiDanBelumSelesaiMilikRelawan(): void
    {
        $wilayah = $this->buatWilayah();
        $akunA = $this->buatAkunRelawan('Relawan Riwayat A');
        $akunB = $this->buatAkunRelawan('Relawan Riwayat B');

        $this->buatLaporan($wilayah->id, $akunA->id, 'Banjir', 'belum_ditangani');
        $this->buatLaporan($wilayah->id, $akunA->id, 'Kebakaran', 'sedang_ditangani');
        $this->buatLaporan($wilayah->id, $akunA->id, 'Gempa Bumi', 'selesai_ditangani', 'selesai');
        $this->buatLaporan($wilayah->id, $akunB->id, 'Tsunami', 'selesai_ditangani', 'selesai');

        Sanctum::actingAs($akunA, [], 'akun_relawan');

        $semua = $this->getJson('/api/v1/relawan/laporan-riwayat');
        $semua->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('ringkasan.belum_selesai', 2)
            ->assertJsonPath('ringkasan.selesai', 1);

        $belum = $this->getJson('/api/v1/relawan/laporan-riwayat?status=belum_selesai');
        $belum->assertOk()->assertJsonCount(2, 'data');
        $jenisBelum = collect($belum->json('data'))->pluck('jenis_kejadian')->all();
        $this->assertContains('Banjir', $jenisBelum);
        $this->assertContains('Kebakaran', $jenisBelum);
        $this->assertNotContains('Gempa Bumi', $jenisBelum);

        $selesai = $this->getJson('/api/v1/relawan/laporan-riwayat?status=selesai');
        $selesai->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_kejadian', 'Gempa Bumi')
            ->assertJsonPath('data.0.status_penanganan', 'selesai_ditangani');
    }

    public function testLaporanSelesaiTidakMunculDiTerdekatTapiAdaDiRiwayat(): void
    {
        $wilayah = $this->buatWilayah();
        $akun = $this->buatAkunRelawan('Relawan Selesai');

        $this->buatLaporan($wilayah->id, $akun->id, 'Banjir', 'sedang_ditangani');
        $this->buatLaporan($wilayah->id, $akun->id, 'Kebakaran', 'selesai_ditangani', 'selesai');

        Sanctum::actingAs($akun, [], 'akun_relawan');

        $this->getJson('/api/v1/relawan/laporan-terdekat')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_kejadian', 'Banjir');

        $this->getJson('/api/v1/relawan/laporan-riwayat?status=selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_kejadian', 'Kebakaran');
    }

    private function buatWilayah(): Wilayah
    {
        return Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);
    }

    private function buatLaporan(
        int $wilayahId,
        int $akunId,
        string $jenis,
        string $penanganan,
        string $status = 'diverifikasi',
    ): LaporanBencana {
        return LaporanBencana::create([
            'wilayah_id' => $wilayahId,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => $jenis,
            'latitude' => -3.6958,
            'longitude' => 128.1810,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => "Deskripsi $jenis",
            'status' => $status,
            'status_penanganan' => $penanganan,
            'akun_relawan_ditugaskan' => $akunId,
            'verified_at' => now(),
        ]);
    }

    private function buatAkunRelawan(string $nama): AkunRelawan
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
            'latitude' => -3.6960,
            'longitude' => 128.1805,
            'lokasi_updated_at' => now(),
        ]);
    }
}
