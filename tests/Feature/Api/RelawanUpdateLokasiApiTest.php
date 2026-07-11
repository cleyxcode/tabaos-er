<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RelawanUpdateLokasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function testUpdateLokasiTriggersAdminNotificationWhenVolunteerArrives(): void
    {
        $admin = User::factory()->create();

        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Relawan API',
            'phone' => '081299988877',
            'email' => 'relawan.api@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.api@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        $laporan = LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456789',
            'jenis_kejadian' => 'Gempa Bumi',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'alamat_lokasi' => 'Ambon',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test laporan',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
            'akun_relawan_ditugaskan' => $akun->id,
        ]);

        Sanctum::actingAs($akun, [], 'akun_relawan');

        $response = $this->putJson('/api/v1/relawan/lokasi', [
            'latitude' => -3.695850,
            'longitude' => 128.181015,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $laporan->refresh();
        $this->assertNotNull($laporan->relawan_sampai_notified_at);
        $this->assertCount(1, $admin->fresh()->notifications);

        $akun->refresh();
        $this->assertSame(-3.695850, (float) $akun->latitude);
        $this->assertSame(128.181015, (float) $akun->longitude);
    }

    public function testUpdateLokasiDoesNotNotifyWhenVolunteerFarFromLaporan(): void
    {
        $admin = User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Relawan Jauh',
            'phone' => '081299988878',
            'email' => 'relawan.jauh@test.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.jauh@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        LaporanBencana::create([
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '08123456780',
            'jenis_kejadian' => 'Banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test',
            'status' => 'ditangani',
            'status_penanganan' => 'sedang_ditangani',
            'akun_relawan_ditugaskan' => $akun->id,
        ]);

        Sanctum::actingAs($akun, [], 'akun_relawan');

        $this->putJson('/api/v1/relawan/lokasi', [
            'latitude' => -3.720000,
            'longitude' => 128.210000,
        ])->assertOk();

        $this->assertCount(0, $admin->fresh()->notifications);
    }
}
