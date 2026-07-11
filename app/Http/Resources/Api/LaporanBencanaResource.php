<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanBencanaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nama_pelapor'     => $this->nama_pelapor,
            'nomor_kontak'     => $this->nomor_kontak,
            'jenis_kejadian'   => $this->jenis_kejadian,

            // ── Lokasi — flat fields (sesuai Flutter model) ──────────────
            'latitude'         => $this->latitude  ? (float) $this->latitude  : null,
            'longitude'        => $this->longitude ? (float) $this->longitude : null,
            'alamat_lokasi'    => $this->alamat_lokasi,

            'wilayah'          => $this->whenLoaded('wilayah', fn () => [
                'id'   => $this->wilayah->id,
                'nama' => $this->wilayah->nama,
            ]),

            'tanggal_kejadian' => $this->tanggal_kejadian?->toISOString(),
            'deskripsi'        => $this->deskripsi,
            'foto'             => collect($this->foto)
                ->map(fn ($path) => url('storage/' . $path))
                ->values(),

            // ── Status ────────────────────────────────────────────────────
            'status'             => $this->status,
            'status_penanganan'  => $this->status_penanganan ?? 'belum_ditangani',

            // ── Pelapor (pengguna) ────────────────────────────────────────
            'pengguna' => $this->whenLoaded('pengguna', fn () => [
                'id'    => $this->pengguna->id,
                'name'  => $this->pengguna->name,
                'phone' => $this->pengguna->phone,
            ]),

            // ── Korban — flat fields (sesuai Flutter model) ───────────────
            'meninggal_jumlah'   => (int) ($this->meninggal_jumlah   ?? 0),
            'hilang_jumlah'      => (int) ($this->hilang_jumlah      ?? 0),
            'luka_berat_jumlah'  => (int) ($this->luka_berat_jumlah  ?? 0),
            'luka_ringan_jumlah' => (int) ($this->luka_ringan_jumlah ?? 0),

            'verified_at' => $this->verified_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
