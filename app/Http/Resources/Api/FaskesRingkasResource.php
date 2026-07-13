<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response ringkas fasilitas kesehatan untuk masyarakat — hanya nama & alamat.
 */
class FaskesRingkasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'kota' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->kota),
        ];
    }
}
