<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunRelawan;
use App\Models\NotifikasiAdmin;
use App\Models\NotifikasiAdminPenerima;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminPesanApiTest extends TestCase
{
    use RefreshDatabase;

    public function testRelawanDapatMelihatDanMenandaiPesanAdmin(): void
    {
        $admin = User::factory()->create();
        $pengguna = Pengguna::create([
            'name' => 'Relawan',
            'phone' => '081234567891',
            'password' => bcrypt('secret'),
        ]);
        $relawan = Relawan::create(['pengguna_id' => $pengguna->id, 'status' => 'disetujui']);
        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'relawan.api@test.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        $notifikasi = NotifikasiAdmin::create([
            'admin_id' => $admin->id,
            'judul' => 'Info Penting',
            'pesan' => 'Harap update lokasi secara berkala.',
            'kirim_ke_relawan' => true,
            'kirim_ke_faskes' => false,
            'status' => 'terkirim',
            'jumlah_penerima' => 1,
            'dikirim_at' => now(),
        ]);

        $inbox = NotifikasiAdminPenerima::create([
            'notifikasi_admin_id' => $notifikasi->id,
            'penerima_type' => AkunRelawan::class,
            'penerima_id' => $akun->id,
        ]);

        Sanctum::actingAs($akun, [], 'akun_relawan');

        $this->getJson('/api/v1/relawan/pesan-admin')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.judul', 'Info Penting');

        $this->putJson("/api/v1/relawan/pesan-admin/{$inbox->id}/baca")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue($inbox->fresh()->sudah_dibaca);
    }
}
