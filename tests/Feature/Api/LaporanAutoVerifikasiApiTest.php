<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Pengguna;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LaporanAutoVerifikasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function testLaporanBaruLangsungDiverifikasiTanpaAccAdmin(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Pelapor Auto',
            'phone' => '081299988877',
            'email' => 'pelapor.auto@test.com',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($pengguna, [], 'pengguna');

        $response = $this->postJson('/api/v1/laporan', [
            'jenis_kejadian' => 'Banjir',
            'di_lokasi_kejadian' => true,
            'latitude' => -3.6958,
            'longitude' => 128.1810,
            'alamat_lokasi' => 'Jl. Merdeka, Ambon',
            'tanggal_kejadian' => now()->toISOString(),
            'deskripsi' => 'Banjir di jalur utama',
            'wilayah_id' => $wilayah->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'diverifikasi');

        $this->assertNotNull($response->json('data.verified_at'));

        $this->assertDatabaseHas('laporan_bencana', [
            'pengguna_id' => $pengguna->id,
            'jenis_kejadian' => 'Banjir',
            'status' => 'diverifikasi',
        ]);
    }
}
