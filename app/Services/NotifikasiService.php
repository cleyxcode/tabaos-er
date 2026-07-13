<?php

namespace App\Services;

use App\Models\LaporanBencana;
use App\Models\RelawanNotifikasi;

class NotifikasiService
{
    public function __construct(
        protected RelawanPenugasanService $penugasan,
        protected FcmV1Client $fcm,
    ) {}

    /**
     * Tugaskan laporan ke satu relawan terdekat dan kirim notifikasi hanya ke relawan tersebut.
     * Dipanggil dari LaporanBencanaObserver::created().
     */
    public function kirimKeRelawanTerdekat(LaporanBencana $laporan, float $radiusKm = 500): void
    {
        if (! $laporan->latitude || ! $laporan->longitude) {
            return;
        }

        $akun = $this->penugasan->tugaskanRelawanTerdekat($laporan, $radiusKm);

        if ($akun === null) {
            return;
        }

        RelawanNotifikasi::create([
            'akun_relawan_id' => $akun->id,
            'laporan_id' => $laporan->id,
            'sudah_dibaca' => false,
        ]);

        if (blank($akun->fcm_token)) {
            return;
        }

        $this->kirimFcm(
            token: $akun->fcm_token,
            title: 'Laporan Bencana Baru',
            body: "Ada laporan {$laporan->jenis_kejadian} di dekat lokasi kamu.",
            data: [
                'laporan_id' => (string) $laporan->id,
                'type' => 'laporan_baru',
            ],
        );
    }

    protected function kirimFcm(string $token, string $title, string $body, array $data = []): void
    {
        $this->kirimPush($token, $title, $body, $data);
    }

    /**
     * Kirim push notification via FCM HTTP v1 ke satu perangkat.
     *
     * @param  array<string, string>  $data
     */
    public function kirimPush(string $token, string $title, string $body, array $data = []): void
    {
        $this->fcm->sendToDevice($token, $title, $body, $data);
    }
}
