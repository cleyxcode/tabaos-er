<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AkunRelawan;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\RelawanNotifikasi;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\RelawanVerifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class RelawanVerifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testVerifikasiCreatesAkunAndNotification(): void
    {
        $admin = User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Budi',
            'phone' => '081200000010',
            'email' => 'budi@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'umur' => 30,
            'alamat' => 'Ambon',
            'keahlian' => 'Medis',
            'status' => 'pending',
        ]);

        $notifikasi = Mockery::mock(NotifikasiService::class);
        $notifikasi->shouldReceive('kirimPush')->never();
        $this->app->instance(NotifikasiService::class, $notifikasi);

        $service = app(RelawanVerifikasiService::class);
        $akun = $service->verifikasi($relawan, $admin->id);

        $this->assertSame('aktif', $akun->status);
        $this->assertSame('budi@test.com', $akun->email);
        $this->assertSame('disetujui', $relawan->fresh()->status);
        $this->assertInstanceOf(AkunRelawan::class, $relawan->fresh()->akunRelawan);
        $this->assertDatabaseHas('relawan_notifikasi', [
            'akun_relawan_id' => $akun->id,
            'jenis' => 'verifikasi',
        ]);
        $this->assertSame(0, $pengguna->fresh()->tokens()->count());
    }

    public function testCannotVerifyTwice(): void
    {
        $admin = User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Ani',
            'phone' => '081200000011',
            'email' => 'ani@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'ani@test.com',
            'password' => bcrypt('password123'),
            'status' => 'aktif',
        ]);

        $service = app(RelawanVerifikasiService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->verifikasi($relawan, $admin->id);
    }

    public function testTolakUpdatesStatus(): void
    {
        $admin = User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Citra',
            'phone' => '081200000012',
            'email' => 'citra@test.com',
            'password' => bcrypt('password123'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'pending',
        ]);

        $service = app(RelawanVerifikasiService::class);
        $result = $service->tolak($relawan, $admin->id);

        $this->assertSame('ditolak', $result->status);
        $this->assertDatabaseMissing('akun_relawan', ['relawan_id' => $relawan->id]);
        $this->assertSame(0, RelawanNotifikasi::count());
    }
}
