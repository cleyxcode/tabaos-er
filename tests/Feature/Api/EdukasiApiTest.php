<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\PedomanBhd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EdukasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexEdukasiMengembalikanMateriPublik(): void
    {
        $admin = User::factory()->create();

        PedomanBhd::create([
            'judul' => 'Cara Evakuasi Tsunami',
            'tipe_file' => 'pdf',
            'deskripsi' => 'Panduan PDF',
            'file_path' => 'edukasi/tsunami.pdf',
            'uploaded_by' => $admin->id,
        ]);

        PedomanBhd::create([
            'judul' => 'Video Pertolongan Pertama',
            'tipe_file' => 'video',
            'deskripsi' => 'Tutorial video',
            'file_path' => 'edukasi/bhd.mp4',
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/edukasi');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('message', 'Data edukasi berhasil diambil.');

        $video = collect($response->json('data'))->firstWhere('tipe_file', 'video');
        $this->assertNotNull($video);
        $this->assertTrue($video['bisa_diputar']);
        $this->assertFalse($video['bisa_dibaca_langsung']);
        $this->assertStringContainsString('storage/edukasi/bhd.mp4', $video['file_url']);

        $pdf = collect($response->json('data'))->firstWhere('tipe_file', 'pdf');
        $this->assertTrue($pdf['bisa_dibaca_langsung']);
        $this->assertSame('application/pdf', $pdf['mime_type']);
    }

    public function testAliasPedomanBhdMasihBerfungsi(): void
    {
        PedomanBhd::create([
            'judul' => 'Foto Materi',
            'tipe_file' => 'gambar',
            'deskripsi' => 'Poster',
            'file_path' => 'edukasi/poster.jpg',
        ]);

        $this->getJson('/api/v1/pedoman-bhd')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Foto Materi');
    }

    public function testFilterTipeFileEdukasi(): void
    {
        PedomanBhd::create([
            'judul' => 'PDF A',
            'tipe_file' => 'pdf',
            'deskripsi' => 'A',
            'file_path' => 'edukasi/a.pdf',
        ]);
        PedomanBhd::create([
            'judul' => 'Video B',
            'tipe_file' => 'video',
            'deskripsi' => 'B',
            'file_path' => 'edukasi/b.mp4',
        ]);

        $this->getJson('/api/v1/edukasi?tipe_file=pdf')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'PDF A');
    }

    public function testShowEdukasi(): void
    {
        $item = PedomanBhd::create([
            'judul' => 'Detail Video',
            'tipe_file' => 'video',
            'deskripsi' => 'Deskripsi lengkap',
            'file_path' => 'edukasi/detail.mp4',
        ]);

        $this->getJson('/api/v1/edukasi/'.$item->id)
            ->assertOk()
            ->assertJsonPath('data.judul', 'Detail Video')
            ->assertJsonPath('data.bisa_diputar', true);
    }
}
