<?php

namespace App\Observers;

use App\Models\LaporanBencana;
use App\Services\NotifikasiService;

class LaporanBencanaObserver
{
    public function __construct(protected NotifikasiService $notifikasi) {}

    public function created(LaporanBencana $laporan): void
    {
        $this->notifikasi->kirimKeRelawanTerdekat($laporan, radiusKm: 10);
    }
}
