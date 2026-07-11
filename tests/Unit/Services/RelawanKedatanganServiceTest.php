<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\HaversineService;
use App\Services\RelawanKedatanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelawanKedatanganServiceTest extends TestCase
{
    use RefreshDatabase;

    private RelawanKedatanganService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RelawanKedatanganService(new HaversineService);
    }

    public function testSudahSampaiReturnsTrueWithinRadius(): void
    {
        $this->assertTrue($this->service->sudahSampai(
            -3.695845,
            128.181011,
            -3.695900,
            128.181050,
        ));
    }

    public function testSudahSampaiReturnsFalseOutsideRadius(): void
    {
        $this->assertFalse($this->service->sudahSampai(
            -3.695845,
            128.181011,
            -3.710000,
            128.200000,
        ));
    }

    public function testPeriksaDanBeritahuAdminCreatesDatabaseNotification(): void
    {
        User::factory()->create(['email' => 'admin@test.com']);

        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Budi Relawan',
            'phone' => '081234567890',
            'email' => 'budi@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.relawan@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        $laporan = LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '081111111111',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon Pusat',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
            'akun_relawan_ditugaskan' => $akun->id,
        ]);

        $this->service->periksaDanBeritahuAdmin(
            $akun,
            -3.695850,
            128.181015,
        );

        $laporan->refresh();
        $this->assertNotNull($laporan->relawan_sampai_notified_at);

        $admin = User::first();
        $this->assertNotNull($admin);
        $this->assertCount(1, $admin->notifications);
    }

    public function testPeriksaDanBeritahuAdminDoesNotDuplicateNotification(): void
    {
        User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Relawan',
            'phone' => '081234567891',
            'email' => 'relawan2@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun2@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        $laporan = LaporanBencana::create([
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '081111111112',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
            'akun_relawan_ditugaskan' => $akun->id,
            'relawan_sampai_notified_at' => now(),
        ]);

        $this->service->periksaDanBeritahuAdmin(
            $akun,
            -3.695850,
            128.181015,
        );

        $admin = User::first();
        $this->assertCount(0, $admin->notifications);
        $this->assertNotNull($laporan->fresh()->relawan_sampai_notified_at);
    }

    public function testPeriksaDanBeritahuAdminIgnoresUnassignedLaporan(): void
    {
        User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Relawan',
            'phone' => '081234567892',
            'email' => 'relawan3@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun3@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        LaporanBencana::create([
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '081111111113',
            'jenis_kejadian' => 'Kebakaran',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'pending',
            'status_penanganan' => 'belum_ditangani',
        ]);

        $this->service->periksaDanBeritahuAdmin(
            $akun,
            -3.695850,
            128.181015,
        );

        $admin = User::first();
        $this->assertCount(0, $admin->notifications);
    }
}
