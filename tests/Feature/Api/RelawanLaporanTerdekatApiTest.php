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

final class RelawanLaporanTerdekatApiTest extends TestCase
{
    use RefreshDatabase;

    public function testRelawanHanyaMelihatLaporanYangDitugaskanKeDirinya(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $akunA = $this->buatAkunRelawan('Relawan A');
        $akunB = $this->buatAkunRelawan('Relawan B');

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor 1',
            'nomor_kontak' => '08111111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Laporan A',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
            'akun_relawan_ditugaskan' => $akunA->id,
        ]);

        LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor 2',
            'nomor_kontak' => '08222222222',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.700000,
            'longitude' => 128.190000,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Laporan B',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
            'akun_relawan_ditugaskan' => $akunB->id,
        ]);

        Sanctum::actingAs($akunA, [], 'akun_relawan');

        $response = $this->getJson('/api/v1/relawan/laporan-terdekat');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_kejadian', 'Banjir');
    }

    public function testRelawanTidakBisaAksesDetailLaporanOrangLain(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $akunA = $this->buatAkunRelawan('Relawan A');
        $akunB = $this->buatAkunRelawan('Relawan B');

        $laporan = LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08111111111',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
            'akun_relawan_ditugaskan' => $akunA->id,
        ]);

        Sanctum::actingAs($akunB, [], 'akun_relawan');

        $this->getJson("/api/v1/relawan/laporan/{$laporan->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
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
