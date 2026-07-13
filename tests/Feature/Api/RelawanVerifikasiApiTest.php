<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelawanVerifikasiApiTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_KEY = 'test-admin-api-key';

    public function testAdminCanVerifyRelawanAndCreateAkun(): void
    {
        User::factory()->create(['id' => 1]);

        $pengguna = Pengguna::create([
            'name' => 'Calon Relawan',
            'phone' => '081234567899',
            'email' => 'calon.relawan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $pengguna->createToken('mobile-app');

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'umur' => 27,
            'alamat' => 'Ambon',
            'keahlian' => 'Evakuasi',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/admin/relawan/{$relawan->id}/verifikasi", [
            'admin_id' => 1,
        ], ['X-Admin-Key' => self::ADMIN_KEY]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.relawan.status', 'disetujui')
            ->assertJsonPath('data.akun_relawan.email', 'calon.relawan@test.com')
            ->assertJsonPath('data.akun_relawan.status', 'aktif');

        $this->assertDatabaseHas('akun_relawan', [
            'email' => 'calon.relawan@test.com',
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('relawan_notifikasi', [
            'jenis' => 'verifikasi',
            'judul' => 'Akun Relawan Diverifikasi',
        ]);

        $this->assertSame(0, $pengguna->fresh()->tokens()->count());
    }

    public function testVerifiedRelawanCannotLoginAsMasyarakat(): void
    {
        User::factory()->create(['id' => 1]);

        $pengguna = Pengguna::create([
            'name' => 'Calon Relawan',
            'phone' => '081234567898',
            'email' => 'sudah.relawan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/admin/relawan/{$relawan->id}/verifikasi", [
            'admin_id' => 1,
        ], ['X-Admin-Key' => self::ADMIN_KEY])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'sudah.relawan@test.com',
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'Akun Anda sudah ditingkatkan menjadi relawan. Silakan login melalui tab Relawan di halaman masuk.',
            ]);
    }

    public function testVerifiedRelawanCanLoginViaRelawanAuth(): void
    {
        User::factory()->create(['id' => 1]);

        $pengguna = Pengguna::create([
            'name' => 'Calon Relawan',
            'phone' => '081234567897',
            'email' => 'login.relawan@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/admin/relawan/{$relawan->id}/verifikasi", [
            'admin_id' => 1,
        ], ['X-Admin-Key' => self::ADMIN_KEY])->assertOk();

        $this->postJson('/api/v1/relawan-auth/login', [
            'email' => 'login.relawan@test.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('akun_relawan.status', 'aktif');
    }

    public function testVerifikasiRequiresAdminKey(): void
    {
        $pengguna = Pengguna::create([
            'name' => 'Calon',
            'phone' => '081234567896',
            'email' => 'calon@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/admin/relawan/{$relawan->id}/verifikasi")
            ->assertUnauthorized();
    }
}
