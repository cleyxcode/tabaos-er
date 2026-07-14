<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Faskes;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FaskesKotaApiTest extends TestCase
{
    use RefreshDatabase;

    public function testLokasiSayaMenampilkanFaskesSekitarLintasKota(): void
    {
        $ambon = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        // Wilayah berbeda (kota/provinsi lain) tapi koordinat dekat Ambon
        $dekat = Wilayah::create([
            'nama' => 'Desa Dekat',
            'kecamatan' => 'Laha',
            'kota' => 'Kabupaten Maluku Tengah',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.7100,
            'longitude' => 128.0900,
        ]);

        $banda = Wilayah::create([
            'nama' => 'Banda Neira',
            'kecamatan' => 'Banda',
            'kota' => 'Banda',
            'pulau' => 'Kepulauan Banda',
            'provinsi' => 'Maluku',
            'latitude' => -4.5267,
            'longitude' => 129.9044,
        ]);

        Faskes::create([
            'wilayah_id' => $ambon->id,
            'nama' => 'RSUD Ambon',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Jl. Ambon',
            'latitude' => -3.6900,
            'longitude' => 128.1850,
        ]);

        Faskes::create([
            'wilayah_id' => $dekat->id,
            'nama' => 'Puskesmas Laha',
            'tipe' => 'puskesmas',
            'alamat' => 'Laha',
            'latitude' => -3.7120,
            'longitude' => 128.0920,
        ]);

        Faskes::create([
            'wilayah_id' => $banda->id,
            'nama' => 'Puskesmas Banda Neira',
            'tipe' => 'puskesmas',
            'alamat' => 'Banda Neira',
            'latitude' => -4.5290,
            'longitude' => 129.9070,
        ]);

        // GPS di Ambon — radius default 75km harus mencakup Ambon + Laha, bukan Banda (~180km)
        $response = $this->getJson('/api/v1/faskes?lat=-3.6960&lng=128.1805');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('nama')->all();
        $this->assertContains('RSUD Ambon', $names);
        $this->assertContains('Puskesmas Laha', $names);
        $this->assertNotContains('Puskesmas Banda Neira', $names);
        $this->assertNotNull($response->json('data.0.jarak_km'));
        $this->assertTrue($response->json('data.0.jarak_km') <= $response->json('data.1.jarak_km'));
    }

    public function testFilterEksplisitPerPulauMasihBerfungsi(): void
    {
        $ambon = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        Faskes::create([
            'wilayah_id' => $ambon->id,
            'nama' => 'RSUD Ambon',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Jl. Ambon',
            'latitude' => -3.6900,
            'longitude' => 128.1850,
        ]);

        $this->getJson('/api/v1/faskes?pulau=Pulau Ambon')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/faskes?pulau=Kepulauan Banda')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function testRadiusKustomBisaMemilihJangkauanLebihKecil(): void
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

        Faskes::create([
            'wilayah_id' => $wilayah->id,
            'nama' => 'RS Dekat',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Dekat',
            'latitude' => -3.6960,
            'longitude' => 128.1810,
        ]);

        Faskes::create([
            'wilayah_id' => $wilayah->id,
            'nama' => 'RS Jauh',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Jauh',
            'latitude' => -3.8000,
            'longitude' => 128.3000,
        ]);

        $this->getJson('/api/v1/faskes?lat=-3.6960&lng=128.1805&radius_km=5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'RS Dekat');
    }
}
