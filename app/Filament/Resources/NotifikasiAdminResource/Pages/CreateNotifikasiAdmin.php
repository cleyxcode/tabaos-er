<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotifikasiAdminResource\Pages;

use App\Filament\Resources\NotifikasiAdminResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateNotifikasiAdmin extends CreateRecord
{
    protected static string $resource = NotifikasiAdminResource::class;

    protected static ?string $title = 'Kirim Pesan ke Relawan & Faskes';

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Kirim Pesan');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['kirim_ke_relawan']) && empty($data['kirim_ke_faskes'])) {
            Notification::make()
                ->title('Pilih minimal satu penerima')
                ->body('Aktifkan Relawan dan/atau Faskes sebagai penerima pesan.')
                ->danger()
                ->send();

            throw new Halt;
        }

        if (! empty($data['kirim_ke_relawan']) && empty($data['kirim_semua_relawan']) && empty($data['akun_relawan_ids'])) {
            Notification::make()
                ->title('Pilih akun relawan')
                ->body('Nonaktifkan "Semua akun relawan aktif" lalu pilih minimal satu akun.')
                ->danger()
                ->send();

            throw new Halt;
        }

        if (! empty($data['kirim_ke_faskes']) && empty($data['kirim_semua_faskes']) && empty($data['akun_faskes_ids'])) {
            Notification::make()
                ->title('Pilih akun faskes')
                ->body('Nonaktifkan "Semua akun faskes aktif" lalu pilih minimal satu akun.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = NotifikasiAdminResource::buatDanKirim($data);

        if ($record->status === 'gagal') {
            Notification::make()
                ->title('Pesan gagal dikirim')
                ->body('Tidak ada akun aktif yang menerima pesan.')
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('Pesan berhasil dikirim')
                ->body("Terkirim ke {$record->jumlah_penerima} akun.")
                ->success()
                ->send();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return NotifikasiAdminResource::getUrl('view', ['record' => $this->record]);
    }
}
