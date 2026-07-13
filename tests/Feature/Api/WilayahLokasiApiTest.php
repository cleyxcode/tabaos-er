<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WilayahLokasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function testDeteksiLokasiMengembalikanKotaDanProvinsi(): void
    {
        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $response = $this->getJson('/api/v1/wilayah/lokasi?lat=-3.6960&lng=128.1805');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kota', 'Kota Ambon')
            ->assertJsonPath('data.provinsi', 'Maluku')
            ->assertJsonPath('data.label', 'Kota Ambon, Maluku');
    }
}
