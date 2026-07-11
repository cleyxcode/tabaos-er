<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotifikasiAdminResource\Pages;

use App\Filament\Resources\NotifikasiAdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListNotifikasiAdmin extends ListRecords
{
    protected static string $resource = NotifikasiAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Kirim Pesan Baru'),
        ];
    }
}
