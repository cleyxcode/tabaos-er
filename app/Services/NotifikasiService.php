<?php

namespace App\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\RelawanNotifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifikasiService
{
    public function __construct(protected HaversineService $haversine) {}

    /**
     * Kirim notifikasi ke semua relawan aktif dalam radius dari lokasi laporan.
     * Dipanggil dari LaporanBencanaObserver::created().
     */
    public function kirimKeRelawanTerdekat(LaporanBencana $laporan, float $radiusKm = 10): void
    {
        if (! $laporan->latitude || ! $laporan->longitude) {
            return;
        }

        $relawanTerdekat = AkunRelawan::where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('fcm_token')
            ->get()
            ->filter(function (AkunRelawan $akun) use ($laporan, $radiusKm) {
                $jarak = $this->haversine->hitungJarak(
                    (float) $laporan->latitude,
                    (float) $laporan->longitude,
                    (float) $akun->latitude,
                    (float) $akun->longitude,
                );
                return $jarak <= $radiusKm;
            });

        foreach ($relawanTerdekat as $akun) {
            RelawanNotifikasi::create([
                'akun_relawan_id' => $akun->id,
                'laporan_id'      => $laporan->id,
                'sudah_dibaca'    => false,
            ]);

            $this->kirimFcm(
                token: $akun->fcm_token,
                title: 'Laporan Bencana Baru',
                body: "Ada laporan {$laporan->jenis_kejadian} di dekat lokasi kamu.",
                data: [
                    'laporan_id' => (string) $laporan->id,
                    'type'       => 'laporan_baru',
                ],
            );
        }
    }

    protected function kirimFcm(string $token, string $title, string $body, array $data = []): void
    {
        $this->kirimPush($token, $title, $body, $data);
    }

    /**
     * Kirim push notification via FCM ke satu perangkat.
     *
     * @param  array<string, string>  $data
     */
    public function kirimPush(string $token, string $title, string $body, array $data = []): void
    {
        try {
            Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM gagal kirim: ' . $e->getMessage());
        }
    }
}
