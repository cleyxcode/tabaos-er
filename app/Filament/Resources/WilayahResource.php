<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WilayahResource\Pages;
use App\Filament\Support\WilayahAdminSupport;
use App\Models\Wilayah;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WilayahResource extends Resource
{
    protected static ?string $model = Wilayah::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';
    protected static string | \UnitEnum | null $navigationGroup = 'Pemetaan';
    protected static ?string $navigationLabel = 'Wilayah';
    protected static ?int $navigationSort = 30;
    protected static ?string $modelLabel = 'Wilayah';
    protected static ?string $pluralModelLabel = 'Wilayah';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('provinsi')
                ->label('Provinsi')
                ->required()
                ->maxLength(100)
                ->placeholder('Contoh: Maluku, Jawa Timur'),
            Forms\Components\TextInput::make('pulau')
                ->label('Pulau')
                ->required()
                ->maxLength(100)
                ->placeholder('Contoh: Pulau Ambon, Kepulauan Banda'),
            Forms\Components\TextInput::make('kota')
                ->label('Kota/Kabupaten')
                ->required()
                ->maxLength(100)
                ->placeholder('Contoh: Kota Ambon, Kabupaten Buru'),
            Forms\Components\TextInput::make('kecamatan')
                ->label('Kecamatan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('nama')
                ->label('Nama Wilayah')
                ->required()
                ->maxLength(255)
                ->helperText('Nama kelurahan/desa atau pusat wilayah untuk deteksi lokasi.'),
            Map::make('location')
                ->label('Koordinat Pusat Wilayah')
                ->columnSpanFull()
                ->defaultLocation(
                    latitude: WilayahAdminSupport::PUSAT_INDONESIA_LAT,
                    longitude: WilayahAdminSupport::PUSAT_INDONESIA_LNG,
                )
                ->draggable()
                ->clickable(true)
                ->zoom(8)
                ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set): void {
                    $set('latitude', $state['lat'] ?? null);
                    $set('longitude', $state['lng'] ?? null);
                })
                ->afterStateHydrated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void {
                    $lat = $get('latitude');
                    $lng = $get('longitude');
                    if ($lat && $lng) {
                        $set('location', ['lat' => $lat, 'lng' => $lng]);
                    }
                })
                ->dehydrated(false),
            Forms\Components\TextInput::make('latitude')
                ->label('Latitude')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pulau')
                    ->label('Pulau')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kota')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Lat')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Lng')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('provinsi')
            ->filters([
                Tables\Filters\SelectFilter::make('provinsi')
                    ->label('Provinsi')
                    ->options(fn (): array => WilayahAdminSupport::provinsiOptions()),
                Tables\Filters\SelectFilter::make('pulau')
                    ->label('Pulau')
                    ->options(fn (): array => WilayahAdminSupport::pulauOptions()),
                Tables\Filters\SelectFilter::make('kota')
                    ->label('Kota/Kabupaten')
                    ->options(fn (): array => WilayahAdminSupport::kotaOptions()),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWilayah::route('/'),
            'create' => Pages\CreateWilayah::route('/create'),
            'edit' => Pages\EditWilayah::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        /** @var Wilayah $record */
        return $record->label_lengkap;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'kecamatan', 'kota', 'pulau', 'provinsi'];
    }
}
