<?php

namespace App\Filament\Resources\AmbulansResource\Pages;

use App\Filament\Resources\AmbulansResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbulans extends ListRecords
{
    protected static string $resource = AmbulansResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
