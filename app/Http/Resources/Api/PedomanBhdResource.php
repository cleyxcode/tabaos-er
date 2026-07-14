<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PedomanBhdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileUrl = $this->resolveFileUrl($this->file_path);

        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'tipe_file' => $this->tipe_file,
            'deskripsi' => $this->deskripsi,
            'file_url' => $fileUrl,
            'file_name' => $this->file_path ? basename((string) $this->file_path) : null,
            'mime_type' => $this->guessMimeType($fileUrl),
            'bisa_diputar' => $this->tipe_file === 'video',
            'bisa_dibaca_langsung' => in_array($this->tipe_file, ['pdf', 'gambar'], true),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function resolveFileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url(Storage::disk('public')->url($path));
    }

    private function guessMimeType(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => match ($this->tipe_file) {
                'pdf' => 'application/pdf',
                'video' => 'video/mp4',
                'gambar' => 'image/jpeg',
                default => 'application/octet-stream',
            },
        };
    }
}
