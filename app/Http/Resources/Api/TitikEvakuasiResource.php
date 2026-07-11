<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitikEvakuasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'nama'      => $this->nama,
            'kapasitas' => $this->kapasitas,
            'fasilitas' => $this->fasilitas,
            'location'  => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
            'zona_rawan' => $this->whenLoaded('zona', fn () => [
                'id'   => $this->zona->id,
                'nama' => $this->zona->nama_zona,
            ]),
        ];
    }
}
