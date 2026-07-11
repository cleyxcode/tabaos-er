<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\ZonaRawanBencana;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;

final class ZonaRawanMapTable
{
    public static function polygonColumn(): TextColumn
    {
        return TextColumn::make('polygon_summary')
            ->label('Area Polygon')
            ->getStateUsing(fn (ZonaRawanBencana $record): string => $record->memilikiPolygon()
                ? $record->polygonTitikCount().' titik'
                : 'Belum digambar')
            ->description(fn (ZonaRawanBencana $record): string => $record->memilikiPolygon()
                ? 'Klik "Lihat Peta" untuk detail koordinat'
                : 'Gambar polygon di form edit')
            ->icon(fn (ZonaRawanBencana $record): ?string => $record->memilikiPolygon()
                ? 'heroicon-m-map'
                : 'heroicon-m-map-pin')
            ->iconColor(fn (ZonaRawanBencana $record): string => $record->memilikiPolygon()
                ? 'success'
                : 'gray')
            ->placeholder('—');
    }

    public static function lihatPetaAction(): Action
    {
        return Action::make('lihat_peta')
            ->label('Lihat Peta')
            ->icon('heroicon-o-map')
            ->color('info')
            ->visible(fn (ZonaRawanBencana $record): bool => $record->memilikiPolygon())
            ->modalHeading(fn (ZonaRawanBencana $record): string => 'Peta: '.$record->nama_zona)
            ->modalDescription(fn (ZonaRawanBencana $record): string => 'Risiko '.ucfirst($record->tingkat_risiko)
                .' · '.$record->polygonTitikCount().' titik polygon')
            ->modalWidth('4xl')
            ->modalContent(fn (ZonaRawanBencana $record) => view(
                'filament.modals.zona-polygon-detail',
                ['zona' => $record->loadMissing('wilayah')],
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
