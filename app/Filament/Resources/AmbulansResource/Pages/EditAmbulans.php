<?php

namespace App\Filament\Resources\AmbulansResource\Pages;

use App\Filament\Resources\AmbulansResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmbulans extends EditRecord
{
    protected static string $resource = AmbulansResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
