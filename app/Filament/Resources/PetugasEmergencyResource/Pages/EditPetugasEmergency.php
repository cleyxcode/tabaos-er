<?php

namespace App\Filament\Resources\PetugasEmergencyResource\Pages;

use App\Filament\Resources\PetugasEmergencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPetugasEmergency extends EditRecord
{
    protected static string $resource = PetugasEmergencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
