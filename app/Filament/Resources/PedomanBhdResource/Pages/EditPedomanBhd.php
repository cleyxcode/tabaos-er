<?php

namespace App\Filament\Resources\PedomanBhdResource\Pages;

use App\Filament\Resources\PedomanBhdResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedomanBhd extends EditRecord
{
    protected static string $resource = PedomanBhdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
