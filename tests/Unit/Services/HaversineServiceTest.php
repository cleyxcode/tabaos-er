<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\HaversineService;
use Tests\TestCase;

final class HaversineServiceTest extends TestCase
{
    public function testHitungJarakAmbonKeTitikDekat(): void
    {
        $service = new HaversineService;

        $jarak = $service->hitungJarak(
            -3.6960,
            128.1805,
            -3.6900,
            128.1850,
        );

        $this->assertGreaterThan(0, $jarak);
        $this->assertLessThan(2, $jarak);
    }

    public function testHitungJarakAmbonKeBandaLebihDariSeratusKm(): void
    {
        $service = new HaversineService;

        $jarak = $service->hitungJarak(
            -3.6960,
            128.1805,
            -4.5290,
            129.9070,
        );

        $this->assertGreaterThan(100, $jarak);
    }
}
