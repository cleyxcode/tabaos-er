<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmbulansResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nama_layanan'  => $this->nama_layanan,
            'nomor_telepon' => $this->nomor_telepon,
            'status'        => $this->status,
            'jenis_layanan' => $this->jenis_layanan,
            'faskes'        => $this->whenLoaded('faskes', fn () => [
                'id'       => $this->faskes->id,
                'nama'     => $this->faskes->nama,
                'location' => $this->faskes->latitude && $this->faskes->longitude ? [
                    'lat' => (float) $this->faskes->latitude,
                    'lng' => (float) $this->faskes->longitude,
                ] : null,
            ]),
        ];
    }
}
