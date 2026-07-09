<?php

namespace App\Filament\Resources\RelawanResource\Pages;

use App\Filament\Resources\RelawanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRelawan extends EditRecord
{
    protected static string $resource = RelawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
