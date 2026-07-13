<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\KirimNotifikasiAdminDTO;
use App\Models\AkunRelawan;
use App\Models\NotifikasiAdmin;
use App\Models\NotifikasiAdminPenerima;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use App\Services\AdminNotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AdminNotifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminNotifikasiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fcm.project_id' => 'tabaos-test',
            'services.fcm.credentials' => base_path('tests/fixtures/firebase-service-account.json'),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://fcm.googleapis.com/v1/*' => Http::response([
                'name' => 'projects/tabaos-test/messages/test-message-id',
            ]),
        ]);

        Cache::put(
            'fcm_v1_access_token_' . sha1('firebase-adminsdk-test@tabaos-test.iam.gserviceaccount.com'),
            'test-access-token',
            3600,
        );

        $this->service = app(AdminNotifikasiService::class);
    }

    public function testKirimKeRelawanAktif(): void
    {
        $admin = User::factory()->create();

        $pengguna = Pengguna::create([
            'name' => 'Relawan',
            'phone' => '081234567890',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'relawan@test.com',
            'password' => bcrypt('secret'),
            'fcm_token' => 'token-relawan',
            'status' => 'aktif',
        ]);

        $notifikasi = $this->service->buatDanKirim(new KirimNotifikasiAdminDTO(
            adminId: $admin->id,
            judul: 'Pengumuman',
            pesan: 'Mohon siaga penuh hari ini.',
            gambar: null,
            kirimKeRelawan: true,
            kirimSemuaRelawan: true,
        ));

        $this->assertSame('terkirim', $notifikasi->status);
        $this->assertSame(1, $notifikasi->jumlah_penerima);
        $this->assertSame(1, NotifikasiAdminPenerima::count());
        Http::assertSentCount(1, fn ($request): bool => str_contains($request->url(), '/messages:send'));
    }

    public function testStatusGagalJikaTidakAdaPenerima(): void
    {
        $admin = User::factory()->create();

        $notifikasi = $this->service->buatDanKirim(new KirimNotifikasiAdminDTO(
            adminId: $admin->id,
            judul: 'Test',
            pesan: 'Test pesan',
            gambar: null,
            kirimKeRelawan: true,
            kirimSemuaRelawan: true,
        ));

        $this->assertSame('gagal', $notifikasi->status);
        $this->assertSame(0, $notifikasi->jumlah_penerima);
    }

    public function testKirimKeRelawanTertentuSaja(): void
    {
        $admin = User::factory()->create();

        $penggunaA = Pengguna::create([
            'name' => 'Relawan A',
            'phone' => '081234567892',
            'password' => bcrypt('secret'),
        ]);
        $penggunaB = Pengguna::create([
            'name' => 'Relawan B',
            'phone' => '081234567893',
            'password' => bcrypt('secret'),
        ]);

        $relawanA = Relawan::create(['pengguna_id' => $penggunaA->id, 'status' => 'disetujui']);
        $relawanB = Relawan::create(['pengguna_id' => $penggunaB->id, 'status' => 'disetujui']);

        $akunA = AkunRelawan::create([
            'relawan_id' => $relawanA->id,
            'email' => 'relawan.a@test.com',
            'password' => bcrypt('secret'),
            'fcm_token' => 'token-a',
            'status' => 'aktif',
        ]);

        AkunRelawan::create([
            'relawan_id' => $relawanB->id,
            'email' => 'relawan.b@test.com',
            'password' => bcrypt('secret'),
            'fcm_token' => 'token-b',
            'status' => 'aktif',
        ]);

        $notifikasi = $this->service->buatDanKirim(new KirimNotifikasiAdminDTO(
            adminId: $admin->id,
            judul: 'Khusus A',
            pesan: 'Hanya untuk relawan A.',
            gambar: null,
            kirimKeRelawan: true,
            kirimSemuaRelawan: false,
            akunRelawanIds: [$akunA->id],
        ));

        $this->assertSame('terkirim', $notifikasi->status);
        $this->assertSame(1, $notifikasi->jumlah_penerima);
        $this->assertSame([$akunA->id], $notifikasi->akun_relawan_ids);
        Http::assertSentCount(1, fn ($request): bool => str_contains($request->url(), '/messages:send'));
    }
}
