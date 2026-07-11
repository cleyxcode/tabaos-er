<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZonaRawanBencanaResource\Pages;
use App\Filament\Resources\ZonaRawanBencanaResource\RelationManagers\TitikEvakuasiRelationManager;
use App\Filament\Support\ZonaRawanMapTable;
use App\Models\ZonaRawanBencana;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ZonaRawanBencanaResource extends Resource
{
    protected static ?string $model = ZonaRawanBencana::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string | \UnitEnum | null $navigationGroup = 'Pemetaan';
    protected static ?string $navigationLabel = 'Zona Rawan Bencana';
    protected static ?int $navigationSort = 31;
    protected static ?string $modelLabel = 'Zona Rawan Bencana';
    protected static ?string $pluralModelLabel = 'Zona Rawan Bencana';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_zona')
                ->label('Nama Zona')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('wilayah_id')
                ->label('Wilayah')
                ->relationship('wilayah', 'nama')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('tingkat_risiko')
                ->label('Tingkat Risiko')
                ->options([
                    'tinggi' => 'Tinggi',
                    'sedang' => 'Sedang',
                    'rendah' => 'Rendah',
                ])
                ->required(),
            Forms\Components\Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\Hidden::make('created_by')
                ->default(fn () => Auth::id()),
            Map::make('polygon')
                ->label('Area Zona Rawan (Polygon)')
                ->helperText('Gunakan ikon polygon atau persegi di toolbar kiri atas peta untuk menggambar area zona rawan.')
                ->columnSpanFull()
                ->defaultLocation(latitude: -3.6954, longitude: 128.1814)
                ->zoom(12)
                ->extraStyles(['min-height: 420px', 'height: 420px'])
                ->draggable()
                ->showMarker(false)
                ->clickable(false)
                ->geoMan(true)
                ->geoManEditable(true)
                ->drawMarker(false)
                ->drawCircle(false)
                ->drawCircleMarker(false)
                ->drawPolyline(false)
                ->drawText(false)
                ->rotateMode(false)
                ->cutPolygon(false)
                ->drawRectangle(true)
                ->drawPolygon(true)
                ->setColor('#ef4444')
                ->setFilledColor('#ef4444')
                ->afterStateHydrated(function (Forms\Components\Field $component, $state): void {
                    $component->state(ZonaRawanBencana::toMapPickerState($state));
                })
                ->dehydrateStateUsing(fn ($state): array => ZonaRawanBencana::extractPolygonFromMapState($state)),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_zona')
                    ->label('Nama Zona')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tingkat_risiko')
                    ->label('Tingkat Risiko')
                    ->colors([
                        'danger' => 'tinggi',
                        'warning' => 'sedang',
                        'success' => 'rendah',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                ZonaRawanMapTable::polygonColumn(),
                Tables\Columns\TextColumn::make('pembuat.name')
                    ->label('Dibuat Oleh')
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tingkat_risiko')
                    ->label('Tingkat Risiko')
                    ->options([
                        'tinggi' => 'Tinggi',
                        'sedang' => 'Sedang',
                        'rendah' => 'Rendah',
                    ]),
            ])
            ->actions([
                ZonaRawanMapTable::lihatPetaAction(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            TitikEvakuasiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZonaRawanBencanas::route('/'),
            'create' => Pages\CreateZonaRawanBencana::route('/create'),
            'edit' => Pages\EditZonaRawanBencana::route('/{record}/edit'),
        ];
    }
}
