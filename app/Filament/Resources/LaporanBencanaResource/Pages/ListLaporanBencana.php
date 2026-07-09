<?php

namespace App\Filament\Resources\LaporanBencanaResource\Pages;

use App\Filament\Resources\LaporanBencanaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanBencana extends ListRecords
{
    protected static string $resource = LaporanBencanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
