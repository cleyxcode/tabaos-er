<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Resources\Api\PedomanBhdResource;
use App\Models\PedomanBhd;
use Illuminate\Http\Request;
use Tests\TestCase;

final class PedomanBhdResourceTest extends TestCase
{
    public function testAplikasiApkDitandaiBisaDiunduhDenganMimeApk(): void
    {
        $model = new PedomanBhd([
            'judul' => 'Simulasi Gempa',
            'tipe_file' => 'aplikasi',
            'deskripsi' => 'APK',
            'file_path' => 'https://cdn.example.com/files/simulasi.apk',
        ]);
        $model->id = 9;

        $payload = (new PedomanBhdResource($model))->toArray(Request::create('/'));

        $this->assertSame('aplikasi', $payload['tipe_file']);
        $this->assertTrue($payload['bisa_diunduh']);
        $this->assertFalse($payload['bisa_diputar']);
        $this->assertFalse($payload['bisa_dibaca_langsung']);
        $this->assertSame('simulasi.apk', $payload['file_name']);
        $this->assertSame(
            'application/vnd.android.package-archive',
            $payload['mime_type']
        );
        $this->assertSame(
            'https://cdn.example.com/files/simulasi.apk',
            $payload['file_url']
        );
    }

    public function testPdfTidakDianggapBisaDiunduhSebagaiAplikasi(): void
    {
        $model = new PedomanBhd([
            'judul' => 'Panduan',
            'tipe_file' => 'pdf',
            'deskripsi' => 'PDF',
            'file_path' => 'https://cdn.example.com/panduan.pdf',
        ]);
        $model->id = 1;

        $payload = (new PedomanBhdResource($model))->toArray(Request::create('/'));

        $this->assertTrue($payload['bisa_dibaca_langsung']);
        $this->assertFalse($payload['bisa_diunduh']);
    }
}
