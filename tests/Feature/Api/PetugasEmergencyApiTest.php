<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PetugasEmergencyApiTest extends TestCase
{
    use RefreshDatabase;

    public function testNomorDaruratReturnsOfficialNumbersWithNames(): void
    {
        $response = $this->getJson('/api/v1/petugas-emergency');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $nomorDarurat = collect($response->json('data.nomor_darurat'));

        $this->assertCount(6, $nomorDarurat);

        $expected = [
            ['nomor' => '119', 'nama' => 'Call Center Layanan Emergency Kesehatan'],
            ['nomor' => '112', 'nama' => 'Call Center Darurat Ambon'],
            ['nomor' => '113', 'nama' => 'Damkar'],
            ['nomor' => '115', 'nama' => 'BASARNAS'],
            ['nomor' => '117', 'nama' => 'BNPB'],
            ['nomor' => '110', 'nama' => 'Call Center Kepolisian'],
        ];

        foreach ($expected as $item) {
            $this->assertTrue(
                $nomorDarurat->contains(fn (array $row): bool => $row['nomor'] === $item['nomor']
                    && $row['nama'] === $item['nama']),
                "Nomor {$item['nomor']} tidak ditemukan dengan nama yang benar.",
            );
        }
    }
}
