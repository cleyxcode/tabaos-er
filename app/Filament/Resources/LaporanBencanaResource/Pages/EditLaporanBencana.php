<?php

namespace App\Filament\Resources\LaporanBencanaResource\Pages;

use App\Filament\Resources\LaporanBencanaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanBencana extends EditRecord
{
    protected static string $resource = LaporanBencanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
