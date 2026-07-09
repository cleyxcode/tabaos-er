<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZonaRawanBencanaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $polygon = $this->polygon;
        if (is_string($polygon)) {
            $polygon = json_decode($polygon, true);
        }

        return [
            'id'            => $this->id,
            'nama_zona'     => $this->nama_zona,
            'tingkat_risiko'=> $this->tingkat_risiko,
            'deskripsi'     => $this->deskripsi,
            'polygon'       => collect($polygon)->map(fn ($point) => [
                'lat' => (float) ($point['lat'] ?? $point[0] ?? 0),
                'lng' => (float) ($point['lng'] ?? $point[1] ?? 0),
            ])->values(),
            'wilayah'       => $this->whenLoaded('wilayah', fn () => [
                'id'   => $this->wilayah->id,
                'nama' => $this->wilayah->nama,
            ]),
            'titik_evakuasi'=> TitikEvakuasiResource::collection($this->whenLoaded('titikEvakuasi')),
        ];
    }
}
