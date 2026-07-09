<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaskesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nama'          => $this->nama,
            'tipe'          => $this->tipe,
            'nomor_telepon' => $this->nomor_telepon,
            'alamat'        => $this->alamat,
            'location'      => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
            'wilayah'       => $this->whenLoaded('wilayah', fn () => [
                'id'   => $this->wilayah->id,
                'nama' => $this->wilayah->nama,
            ]),
            'ambulans'      => AmbulansResource::collection($this->whenLoaded('ambulans')),
            // Distance injected from controller when querying by proximity
            'jarak_km'      => isset($this->jarak_km) ? round($this->jarak_km, 2) : null,
        ];
    }
}
