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
            'tipe' => $this->tipe,
            'alamat' => $this->alamat,
            'nomor_telepon' => $this->nomor_telepon,
            'kota' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->kota),
            'pulau' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->pulau),
            'provinsi' => $this->whenLoaded('wilayah', fn () => $this->wilayah?->provinsi),
            'location' => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
            'jarak_km' => isset($this->jarak_km) ? round((float) $this->jarak_km, 2) : null,
        ];
    }
}
