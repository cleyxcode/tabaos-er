<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelawanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nik'         => $this->nik,
            'alamat'      => $this->alamat,
            'keahlian'    => $this->keahlian,
            'status'      => $this->status,
            'pengguna'    => $this->whenLoaded('pengguna', fn () => [
                'id'    => $this->pengguna->id,
                'name'  => $this->pengguna->name,
                'phone' => $this->pengguna->phone,
            ]),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
