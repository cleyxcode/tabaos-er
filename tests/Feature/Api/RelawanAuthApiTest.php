<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunRelawan;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelawanAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function testRelawanCanLoginWithValidCredentials(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Relawan Aktif',
            'phone' => '081200000001',
            'email' => 'relawan.aktif@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'umur' => 25,
            'alamat' => 'Ambon',
            'keahlian' => 'Medis',
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'relawan.aktif@test.com',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/v1/relawan-auth/login', [
            'email' => 'relawan.aktif@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('token', fn ($v) => is_string($v) && $v !== '')
            ->assertJsonPath('akun_relawan.email', 'relawan.aktif@test.com')
            ->assertJsonPath('akun_relawan.status', 'aktif');
    }

    public function testRelawanLoginFailsWithWrongPassword(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Relawan',
            'phone' => '081200000002',
            'email' => 'relawan.salah@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'relawan.salah@test.com',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);

        $this->postJson('/api/v1/relawan-auth/login', [
            'email' => 'relawan.salah@test.com',
            'password' => 'salah',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function testNonaktifRelawanCannotLogin(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Relawan Nonaktif',
            'phone' => '081200000003',
            'email' => 'relawan.nonaktif@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'relawan.nonaktif@test.com',
            'password' => bcrypt('password123'),
            'status' => 'nonaktif',
        ]);

        $this->postJson('/api/v1/relawan-auth/login', [
            'email' => 'relawan.nonaktif@test.com',
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Akun ini tidak aktif. Hubungi administrator.');
    }
}
