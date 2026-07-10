<?php

namespace App\Filament\Resources\AkunRelawanResource\Pages;

use App\Filament\Resources\AkunRelawanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkunRelawan extends EditRecord
{
    protected static string $resource = AkunRelawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
