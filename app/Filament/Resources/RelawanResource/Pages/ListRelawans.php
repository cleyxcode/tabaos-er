<?php

namespace App\Filament\Resources\RelawanResource\Pages;

use App\Filament\Resources\RelawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRelawans extends ListRecords
{
    protected static string $resource = RelawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
