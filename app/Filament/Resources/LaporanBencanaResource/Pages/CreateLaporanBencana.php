<?php

namespace App\Filament\Resources\LaporanBencanaResource\Pages;

use App\Filament\Resources\LaporanBencanaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanBencana extends CreateRecord
{
    protected static string $resource = LaporanBencanaResource::class;

    /**
     * Laporan baru langsung diverifikasi (tanpa menunggu ACC admin).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? 'diverifikasi';
        $data['verified_at'] = $data['verified_at'] ?? now();
        $data['verified_by'] = $data['verified_by'] ?? auth()->id();

        return $data;
    }
}
