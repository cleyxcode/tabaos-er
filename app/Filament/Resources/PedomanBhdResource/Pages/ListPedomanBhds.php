<?php

namespace App\Filament\Resources\PedomanBhdResource\Pages;

use App\Filament\Resources\PedomanBhdResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedomanBhds extends ListRecords
{
    protected static string $resource = PedomanBhdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
