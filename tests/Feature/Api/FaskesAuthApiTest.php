<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunFaskes;
use App\Models\Faskes;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FaskesAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function testFaskesCanLoginWithValidCredentials(): void
    {
        $wilayah = Wilayah::create(['nama' => 'Ambon', 'kecamatan' => 'Sirimau', 'kota' => 'Ambon']);
        $faskes = Faskes::create([
            'wilayah_id' => $wilayah->id,
            'nama' => 'RSUD',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Ambon',
            'latitude' => -3.69,
            'longitude' => 128.18,
        ]);

        AkunFaskes::create([
            'faskes_id' => $faskes->id,
            'nama_petugas' => 'Petugas RSUD',
            'email' => 'faskes@test.com',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/v1/faskes-auth/login', [
            'email' => 'faskes@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('token', fn ($v) => is_string($v) && $v !== '')
            ->assertJsonPath('akun_faskes.email', 'faskes@test.com');
    }

    public function testFaskesLoginFailsWithWrongPassword(): void
    {
        $wilayah = Wilayah::create(['nama' => 'Ambon', 'kecamatan' => 'Sirimau', 'kota' => 'Ambon']);
        $faskes = Faskes::create([
            'wilayah_id' => $wilayah->id,
            'nama' => 'RSUD',
            'tipe' => 'rumah_sakit',
            'alamat' => 'Ambon',
            'latitude' => -3.69,
            'longitude' => 128.18,
        ]);

        AkunFaskes::create([
            'faskes_id' => $faskes->id,
            'nama_petugas' => 'Petugas',
            'email' => 'faskes.salah@test.com',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);

        $this->postJson('/api/v1/faskes-auth/login', [
            'email' => 'faskes.salah@test.com',
            'password' => 'salah',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }
}
