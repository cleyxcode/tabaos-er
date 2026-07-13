<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelawanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'is_registered'    => true,
            'umur'             => $this->umur,
            'alamat'           => $this->alamat,
            'keahlian'         => $this->keahlian,
            'organisasi'       => $this->organisasi,
            'status'           => $this->status,
            'is_verified'      => $this->status === 'disetujui',
            'has_akun_relawan' => $this->akunRelawan !== null,
            'akun_email'       => $this->akunRelawan?->email,
            'pengguna'         => $this->whenLoaded('pengguna', fn () => [
                'id'    => $this->pengguna->id,
                'name'  => $this->pengguna->name,
                'phone' => $this->pengguna->phone,
            ]),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
