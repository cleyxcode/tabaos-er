<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotifikasiAdminResource\Pages;

use App\Filament\Resources\NotifikasiAdminResource;
use App\Models\AkunFaskes;
use App\Models\AkunRelawan;
use App\Models\NotifikasiAdmin;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

final class ViewNotifikasiAdmin extends ViewRecord
{
    protected static string $resource = NotifikasiAdminResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Detail Pesan')
                ->schema([
                    TextEntry::make('judul')->label('Judul'),
                    TextEntry::make('pesan')
                        ->label('Pesan')
                        ->columnSpanFull(),
                    ImageEntry::make('gambar')
                        ->label('Gambar')
                        ->disk('public')
                        ->visible(fn (NotifikasiAdmin $record): bool => filled($record->gambar))
                        ->columnSpanFull(),
                    TextEntry::make('admin.name')->label('Dikirim Oleh'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'draft' => 'Draft',
                            'terkirim' => 'Terkirim',
                            'gagal' => 'Gagal',
                            default => $state,
                        }),
                    TextEntry::make('jumlah_penerima')->label('Jumlah Penerima'),
                    TextEntry::make('dikirim_at')
                        ->label('Waktu Kirim')
                        ->dateTime('d M Y H:i'),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Target Penerima')
                ->schema([
                    TextEntry::make('kirim_ke_relawan')
                        ->label('Relawan')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                    TextEntry::make('relawan_target')
                        ->label('Cakupan Relawan')
                        ->state(fn (NotifikasiAdmin $record): string => self::formatRelawanTarget($record))
                        ->visible(fn (NotifikasiAdmin $record): bool => $record->kirim_ke_relawan)
                        ->columnSpanFull(),
                    TextEntry::make('kirim_ke_faskes')
                        ->label('Faskes')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                    TextEntry::make('faskes_target')
                        ->label('Cakupan Faskes')
                        ->state(fn (NotifikasiAdmin $record): string => self::formatFaskesTarget($record))
                        ->visible(fn (NotifikasiAdmin $record): bool => $record->kirim_ke_faskes)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    private static function formatRelawanTarget(NotifikasiAdmin $record): string
    {
        if ($record->kirim_semua_relawan) {
            return 'Semua akun relawan aktif';
        }

        $ids = $record->akun_relawan_ids ?? [];
        if ($ids === []) {
            return '-';
        }

        return AkunRelawan::query()
            ->with('relawan.pengguna')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (AkunRelawan $akun): string => NotifikasiAdminResource::labelAkunRelawan($akun))
            ->join(', ');
    }

    private static function formatFaskesTarget(NotifikasiAdmin $record): string
    {
        if ($record->kirim_semua_faskes) {
            return 'Semua akun faskes aktif';
        }

        $ids = $record->akun_faskes_ids ?? [];
        if ($ids === []) {
            return '-';
        }

        return AkunFaskes::query()
            ->with('faskes')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (AkunFaskes $akun): string => NotifikasiAdminResource::labelAkunFaskes($akun))
            ->join(', ');
    }
}
