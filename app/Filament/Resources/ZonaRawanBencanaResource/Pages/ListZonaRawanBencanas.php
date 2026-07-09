<?php

namespace App\Filament\Resources\ZonaRawanBencanaResource\Pages;

use App\Filament\Resources\ZonaRawanBencanaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZonaRawanBencanas extends ListRecords
{
    protected static string $resource = ZonaRawanBencanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
