<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedomanBhdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'judul'       => $this->judul,
            'tipe_file'   => $this->tipe_file,
            'deskripsi'   => $this->deskripsi,
            'file_url'    => $this->file_path ? url('storage/' . $this->file_path) : null,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
