<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WilayahLokasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function testDeteksiLokasiMengembalikanKotaPulauDanProvinsi(): void
    {
        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $response = $this->getJson('/api/v1/wilayah/lokasi?lat=-3.6960&lng=128.1805');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kota', 'Kota Ambon')
            ->assertJsonPath('data.pulau', 'Pulau Ambon')
            ->assertJsonPath('data.provinsi', 'Maluku')
            ->assertJsonPath('data.label', 'Pulau Ambon, Maluku');
    }

    public function testOpsiFilterMengembalikanDaftarWilayah(): void
    {
        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'pulau' => 'Pulau Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        Wilayah::create([
            'nama' => 'Banda Neira',
            'kecamatan' => 'Banda',
            'kota' => 'Banda',
            'pulau' => 'Kepulauan Banda',
            'provinsi' => 'Maluku',
            'latitude' => -4.5267,
            'longitude' => 129.9044,
        ]);

        $response = $this->getJson('/api/v1/wilayah/opsi');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['Maluku'])
            ->assertJsonPath('data.pulau.0', 'Kepulauan Banda')
            ->assertJsonPath('data.pulau.1', 'Pulau Ambon');
    }
}
