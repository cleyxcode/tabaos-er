<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanBencanaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'nama_pelapor'             => $this->nama_pelapor,
            'nomor_kontak'             => $this->nomor_kontak,
            'jenis_kejadian'           => $this->jenis_kejadian,
            'di_lokasi_kejadian'       => (bool) $this->di_lokasi_kejadian,
            'location'                 => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
            'alamat_lokasi'            => $this->alamat_lokasi,
            'wilayah'                  => $this->whenLoaded('wilayah', fn () => [
                'id'   => $this->wilayah->id,
                'nama' => $this->wilayah->nama,
            ]),
            'tanggal_kejadian'         => $this->tanggal_kejadian?->toISOString(),
            'deskripsi'                => $this->deskripsi,
            'foto'                     => collect($this->foto)->map(fn ($path) => url('storage/' . $path))->values(),
            'status'                   => $this->status,
            // Korban
            'meninggal'                => [
                'jumlah'        => $this->meninggal_jumlah ?? 0,
                'jenis_kelamin' => $this->meninggal_jenis_kelamin,
                'penyebab'      => $this->penyebab_meninggal,
            ],
            'hilang'                   => [
                'jumlah'        => $this->hilang_jumlah ?? 0,
                'jenis_kelamin' => $this->hilang_jenis_kelamin,
            ],
            'luka_berat'               => [
                'jumlah'        => $this->luka_berat_jumlah ?? 0,
                'jenis_kelamin' => $this->luka_berat_jenis_kelamin,
                'penyebab'      => $this->penyebab_luka_berat,
            ],
            'luka_ringan'              => [
                'jumlah'        => $this->luka_ringan_jumlah ?? 0,
                'jenis_kelamin' => $this->luka_ringan_jenis_kelamin,
                'penyebab'      => $this->penyebab_luka_ringan,
            ],
            'verified_at'              => $this->verified_at?->toISOString(),
            'created_at'               => $this->created_at?->toISOString(),
        ];
    }
}
