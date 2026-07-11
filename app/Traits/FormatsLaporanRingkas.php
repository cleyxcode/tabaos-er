<?php

namespace App\Traits;

use App\Models\LaporanBencana;

trait FormatsLaporanRingkas
{
    protected function formatLaporanRingkas(LaporanBencana $item): array
    {
        return [
            'id'                => $item->id,
            'jenis_kejadian'    => $item->jenis_kejadian,
            'deskripsi'         => $item->deskripsi,
            'status'            => $item->status,
            'status_penanganan' => $item->status_penanganan ?? 'belum_ditangani',
            'latitude'          => $item->latitude,
            'longitude'         => $item->longitude,
            'alamat_lokasi'     => $item->alamat_lokasi,
            'tanggal_kejadian'  => $item->tanggal_kejadian?->toISOString(),
            'created_at'        => $item->created_at?->toISOString(),
            'korban'            => [
                'meninggal_jumlah'   => (int) ($item->meninggal_jumlah   ?? 0),
                'luka_berat_jumlah'  => (int) ($item->luka_berat_jumlah  ?? 0),
                'luka_ringan_jumlah' => (int) ($item->luka_ringan_jumlah ?? 0),
                'hilang_jumlah'      => (int) ($item->hilang_jumlah      ?? 0),
            ],
            'jarak_km' => isset($item->jarak_km) ? round((float) $item->jarak_km, 2) : null,
            'relawan_ditugaskan' => $item->relationLoaded('relawanDitugaskan') && $item->relawanDitugaskan ? [
                'id'   => $item->relawanDitugaskan->id,
                'nama' => $item->relawanDitugaskan->relawan?->pengguna?->name,
            ] : null,
        ];
    }
}
