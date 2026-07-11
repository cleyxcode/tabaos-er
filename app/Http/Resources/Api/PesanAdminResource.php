<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\NotifikasiAdminPenerima;
use App\Services\AdminNotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotifikasiAdminPenerima */
final class PesanAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notifikasi = $this->notifikasi;
        $gambarService = app(AdminNotifikasiService::class);

        return [
            'inbox_id' => $this->id,
            'id' => $notifikasi?->id,
            'judul' => $notifikasi?->judul,
            'pesan' => $notifikasi?->pesan,
            'gambar_url' => $gambarService->gambarUrl($notifikasi?->gambar),
            'dari_admin' => $notifikasi?->admin?->name,
            'sudah_dibaca' => $this->sudah_dibaca,
            'dibaca_at' => $this->dibaca_at?->toISOString(),
            'dikirim_at' => $notifikasi?->dikirim_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
