<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetugasEmergencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nama'           => $this->nama,
            'kategori'       => $this->kategori,
            'nomor_telepon'  => $this->nomor_telepon,
            'status'         => $this->status,
            'alamat'         => $this->alamat,
            'location'       => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
        ];
    }
}
