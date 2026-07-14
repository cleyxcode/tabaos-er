<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response ringkas fasilitas kesehatan untuk masyarakat.
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
            'pulau' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->pulau),
            'provinsi' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->provinsi),
            'jarak_km' => isset($this->jarak_km) ? round((float) $this->jarak_km, 2) : null,
        ];
    }
}
