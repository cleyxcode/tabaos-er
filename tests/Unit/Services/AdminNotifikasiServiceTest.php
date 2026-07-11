<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\KirimNotifikasiAdminDTO;
use App\Models\AkunFaskes;
use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\NotifikasiAdmin;
use App\Models\NotifikasiAdminPenerima;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AdminNotifikasiService;
use App\Services\HaversineService;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AdminNotifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminNotifikasiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['success' => 1])]);
        $this->service = new AdminNotifikasiService(new NotifikasiService(new HaversineService));
    }

    public function testKirimKeRelawanDanFaskesAktif(): void
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
            'email' => 'faskes@test.com',
            'password' => bcrypt('secret'),
            'fcm_token' => 'token-faskes',
            'status' => 'aktif',
        ]);

        $notifikasi = $this->service->buatDanKirim(new KirimNotifikasiAdminDTO(
            adminId: $admin->id,
            judul: 'Pengumuman',
            pesan: 'Mohon siaga penuh hari ini.',
            gambar: null,
            kirimKeRelawan: true,
            kirimKeFaskes: true,
            kirimSemuaRelawan: true,
            kirimSemuaFaskes: true,
        ));

        $this->assertSame('terkirim', $notifikasi->status);
        $this->assertSame(2, $notifikasi->jumlah_penerima);
        $this->assertSame(2, NotifikasiAdminPenerima::count());
        Http::assertSentCount(2);
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
            kirimKeFaskes: false,
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
            kirimKeFaskes: false,
            kirimSemuaRelawan: false,
            akunRelawanIds: [$akunA->id],
        ));

        $this->assertSame('terkirim', $notifikasi->status);
        $this->assertSame(1, $notifikasi->jumlah_penerima);
        $this->assertSame([$akunA->id], $notifikasi->akun_relawan_ids);
        Http::assertSentCount(1);
    }
}
