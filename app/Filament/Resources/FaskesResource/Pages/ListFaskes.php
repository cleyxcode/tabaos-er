<?php

namespace App\Filament\Resources\FaskesResource\Pages;

use App\Filament\Resources\FaskesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaskes extends ListRecords
{
    protected static string $resource = FaskesResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
