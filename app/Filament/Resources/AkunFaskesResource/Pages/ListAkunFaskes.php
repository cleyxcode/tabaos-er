<?php

namespace App\Filament\Resources\AkunFaskesResource\Pages;

use App\Filament\Resources\AkunFaskesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAkunFaskes extends ListRecords
{
    protected static string $resource = AkunFaskesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
