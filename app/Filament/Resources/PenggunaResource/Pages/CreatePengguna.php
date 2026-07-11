<?php

namespace App\Filament\Resources\PenggunaResource\Pages;

use App\Filament\Resources\PenggunaResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePengguna extends CreateRecord
{
    protected static string $resource = PenggunaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengguna berhasil ditambahkan';
    }
}
