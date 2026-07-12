<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AkunRelawanDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function testDeleteAkunRelawanNullifiesAssignedLaporanReferences(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Relawan Test',
            'phone' => '081234567890',
            'email' => 'relawan.test@example.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.test@example.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        $laporan = LaporanBencana::create([
            'pengguna_id' => $pengguna->id,
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '081111111111',
            'jenis_kejadian' => 'banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test laporan',
            'status' => 'verified',
            'akun_relawan_ditugaskan' => $akun->id,
            'status_penanganan' => 'sedang_ditangani',
        ]);

        $akun->delete();

        $this->assertDatabaseMissing('akun_relawan', ['id' => $akun->id]);
        $this->assertDatabaseHas('laporan_bencana', [
            'id' => $laporan->id,
            'akun_relawan_ditugaskan' => null,
        ]);
    }

    public function testDeleteAkunRelawanWorksWhenForeignKeyHasNoNullOnDelete(): void
    {
        $wilayah = Wilayah::create([
            'nama' => 'Sirimau',
            'kecamatan' => 'Sirimau',
            'kota' => 'Ambon',
        ]);

        $pengguna = Pengguna::create([
            'name' => 'Relawan Restrict',
            'phone' => '081234567891',
            'email' => 'relawan.restrict@example.com',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        $akun = AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.restrict@example.com',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
        ]);

        LaporanBencana::create([
            'pengguna_id' => $pengguna->id,
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor',
            'nomor_kontak' => '081111111112',
            'jenis_kejadian' => 'banjir',
            'latitude' => -3.695845,
            'longitude' => 128.181011,
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Test laporan',
            'status' => 'verified',
            'akun_relawan_ditugaskan' => $akun->id,
            'status_penanganan' => 'sedang_ditangani',
        ]);

        \Illuminate\Support\Facades\Schema::table('laporan_bencana', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->dropForeign(['akun_relawan_ditugaskan']);
        });

        \Illuminate\Support\Facades\Schema::table('laporan_bencana', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->foreign('akun_relawan_ditugaskan')
                ->references('id')
                ->on('akun_relawan');
        });

        $akun->delete();

        $this->assertDatabaseMissing('akun_relawan', ['id' => $akun->id]);
    }
}
