<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Wilayah;
use App\Services\HaversineService;
use App\Services\WilayahLokasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WilayahLokasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private WilayahLokasiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WilayahLokasiService(new HaversineService);
    }

    public function testDeteksiKotaDariKoordinatTerdekat(): void
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

        $this->assertSame('Kota Ambon', $this->service->deteksiKota(-3.6960, 128.1805));
        $this->assertSame('Tual', $this->service->deteksiKota(-5.6425, 132.7485));
    }

    public function testDeteksiProvinsiDariKoordinat(): void
    {
        Wilayah::create([
            'nama' => 'Kota Ambon',
            'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon',
            'provinsi' => 'Maluku',
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $this->assertSame('Maluku', $this->service->deteksiProvinsi(-3.6960, 128.1805));
    }
}
