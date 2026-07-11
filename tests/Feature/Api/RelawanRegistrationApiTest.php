<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Pengguna;
use App\Models\Relawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RelawanRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function testPenggunaCanRegisterAsRelawan(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Budi Masyarakat',
            'phone' => '081234567890',
            'email' => 'budi.masyarakat@test.com',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($pengguna, [], 'pengguna');

        $response = $this->postJson('/api/v1/relawan', [
            'nik' => '1234567890123456',
            'alamat' => 'Jl. Merdeka No. 1, Ambon',
            'keahlian' => 'Medis',
            'organisasi' => 'PMI Ambon',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nik', '1234567890123456')
            ->assertJsonPath('data.keahlian', 'Medis')
            ->assertJsonPath('data.organisasi', 'PMI Ambon')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('relawan', [
            'pengguna_id' => $pengguna->id,
            'nik' => '1234567890123456',
            'organisasi' => 'PMI Ambon',
            'status' => 'pending',
        ]);
    }

    public function testRegisterRelawanWithoutOrganisasiSucceeds(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Ani Masyarakat',
            'phone' => '081234567891',
            'email' => 'ani.masyarakat@test.com',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($pengguna, [], 'pengguna');

        $this->postJson('/api/v1/relawan', [
            'nik' => '9876543210987654',
            'alamat' => 'Ambon',
            'keahlian' => 'Evakuasi',
        ])->assertCreated();

        $this->assertDatabaseHas('relawan', [
            'pengguna_id' => $pengguna->id,
            'organisasi' => null,
        ]);
    }

    public function testCannotRegisterTwiceAsRelawan(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Citra Masyarakat',
            'phone' => '081234567892',
            'email' => 'citra.masyarakat@test.com',
            'password' => bcrypt('password123'),
        ]);

        Relawan::create([
            'pengguna_id' => $pengguna->id,
            'nik' => '1111111111111111',
            'alamat' => 'Ambon',
            'keahlian' => 'Medis',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($pengguna, [], 'pengguna');

        $this->postJson('/api/v1/relawan', [
            'nik' => '2222222222222222',
            'alamat' => 'Ambon',
            'keahlian' => 'Logistik',
        ])->assertStatus(409);
    }
}
