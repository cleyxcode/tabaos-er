<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\LaporanBencana;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;

final class LaporanLokasiTable
{
    public static function locationColumn(): TextColumn
    {
        return TextColumn::make('alamat_lokasi')
            ->label('Lokasi Laporan')
            ->searchable()
            ->limit(40)
            ->tooltip(fn (LaporanBencana $record): ?string => $record->alamat_lokasi)
            ->description(fn (LaporanBencana $record): string => $record->memilikiKoordinat()
                ? $record->koordinatLabel()
                : 'Koordinat belum tersedia')
            ->icon(fn (LaporanBencana $record): ?string => $record->memilikiKoordinat()
                ? 'heroicon-m-map-pin'
                : 'heroicon-m-map')
            ->iconColor(fn (LaporanBencana $record): string => $record->memilikiKoordinat()
                ? 'success'
                : 'gray')
            ->placeholder('Alamat tidak diisi')
            ->wrap();
    }

    public static function lihatLokasiAction(): Action
    {
        return Action::make('lihat_lokasi')
            ->label('Lihat Lokasi')
            ->icon('heroicon-o-map')
            ->color('info')
            ->visible(fn (LaporanBencana $record): bool => $record->memilikiKoordinat())
            ->modalHeading(fn (LaporanBencana $record): string => 'Lokasi: '.$record->jenis_kejadian)
            ->modalDescription(fn (LaporanBencana $record): string => 'Pelapor: '.$record->nama_pelapor
                .' · '.$record->tanggal_kejadian?->format('d M Y, H:i'))
            ->modalWidth('3xl')
            ->modalContent(fn (LaporanBencana $record) => view(
                'filament.modals.laporan-lokasi',
                ['laporan' => $record->loadMissing('wilayah')],
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->extraModalFooterActions(fn (LaporanBencana $record): array => [
                Action::make('buka_google_maps')
                    ->label('Buka di Google Maps')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url($record->googleMapsUrl(), shouldOpenInNewTab: true),
            ]);
    }
}
