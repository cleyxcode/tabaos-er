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

    public function testFaskesHanyaMenampilkanFasilitasDiKotaPengguna(): void
    {
        $ambon = Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $tual = Wilayah::create([
            'nama' => 'Tual',
            'kecamatan' => 'Tual Kota',
            'kota' => 'Tual',
            'provinsi' => 'Maluku',
            'latitude' => -5.6417,
            'longitude' => 132.7472,
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
            'wilayah_id' => $tual->id,
            'nama' => 'RSUD Tual',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Jl. Tual',
            'latitude' => -5.6430,
            'longitude' => 132.7490,
        ]);

        $response = $this->getJson('/api/v1/faskes?lat=-3.6960&lng=128.1805');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'RSUD Ambon')
            ->assertJsonPath('data.0.alamat', 'Jl. Ambon')
            ->assertJsonMissingPath('data.0.nomor_telepon')
            ->assertJsonMissingPath('data.0.location');
    }
}
