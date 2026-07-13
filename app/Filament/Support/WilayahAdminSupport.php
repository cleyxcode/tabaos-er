<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Wilayah;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

final class WilayahAdminSupport
{
    /** Pusat peta Indonesia (fallback multi-provinsi). */
    public const PUSAT_INDONESIA_LAT = -2.548926;

    public const PUSAT_INDONESIA_LNG = 118.014863;

    public const PUSAT_INDONESIA_ZOOM = 5;

    /**
     * @return array<string, string>
     */
    public static function provinsiOptions(): array
    {
        return Wilayah::query()
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi', 'provinsi')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function kotaOptions(?string $provinsi = null): array
    {
        return Wilayah::query()
            ->when($provinsi, fn (Builder $q) => $q->where('provinsi', $provinsi))
            ->whereNotNull('kota')
            ->where('kota', '!=', '')
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota', 'kota')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function wilayahOptions(?string $provinsi = null, ?string $kota = null): array
    {
        return Wilayah::query()
            ->when($provinsi, fn (Builder $q) => $q->where('provinsi', $provinsi))
            ->when($kota, fn (Builder $q) => $q->where('kota', $kota))
            ->orderBy('provinsi')
            ->orderBy('kota')
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Wilayah $w) => [$w->id => $w->label_lengkap])
            ->all();
    }

    /**
     * @return array{lat: float, lng: float, zoom: int}
     */
    public static function petaCenter(?int $wilayahId = null, ?string $provinsi = null, ?string $kota = null): array
    {
        $query = Wilayah::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($wilayahId !== null) {
            $query->where('id', $wilayahId);
        } elseif ($kota !== null) {
            $query->where('kota', $kota);
        } elseif ($provinsi !== null) {
            $query->where('provinsi', $provinsi);
        }

        $coords = $query->get(['latitude', 'longitude']);

        if ($coords->isEmpty()) {
            return [
                'lat' => self::PUSAT_INDONESIA_LAT,
                'lng' => self::PUSAT_INDONESIA_LNG,
                'zoom' => self::PUSAT_INDONESIA_ZOOM,
            ];
        }

        return [
            'lat' => round((float) $coords->avg('latitude'), 6),
            'lng' => round((float) $coords->avg('longitude'), 6),
            'zoom' => match (true) {
                $wilayahId !== null => 12,
                $kota !== null => 10,
                $provinsi !== null => 7,
                default => 6,
            },
        ];
    }

    public static function wilayahSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('wilayah_id')
            ->label('Wilayah')
            ->options(fn (): array => self::wilayahOptions())
            ->getOptionLabelFromRecordUsing(fn (Wilayah $record): string => $record->label_lengkap)
            ->searchable()
            ->preload()
            ->nullable();
    }

    /**
     * @return array<int, Tables\Filters\Filter|Tables\Filters\SelectFilter>
     */
    public static function tableFilters(string $wilayahRelation = 'wilayah'): array
    {
        return [
            Tables\Filters\SelectFilter::make('provinsi')
                ->label('Provinsi')
                ->options(fn (): array => self::provinsiOptions())
                ->query(function (Builder $query, array $data) use ($wilayahRelation): Builder {
                    $value = $data['value'] ?? null;
                    if (blank($value)) {
                        return $query;
                    }

                    return $query->whereHas($wilayahRelation, fn (Builder $q) => $q->where('provinsi', $value));
                }),

            Tables\Filters\SelectFilter::make('kota')
                ->label('Kota/Kabupaten')
                ->options(fn (): array => self::kotaOptions())
                ->query(function (Builder $query, array $data) use ($wilayahRelation): Builder {
                    $value = $data['value'] ?? null;
                    if (blank($value)) {
                        return $query;
                    }

                    return $query->whereHas($wilayahRelation, fn (Builder $q) => $q->where('kota', $value));
                }),

            Tables\Filters\SelectFilter::make('wilayah_id')
                ->label('Wilayah')
                ->options(fn (): array => self::wilayahOptions())
                ->searchable(),
        ];
    }
}
