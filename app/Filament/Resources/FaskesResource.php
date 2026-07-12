<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaskesResource\Pages;
use App\Filament\Resources\FaskesResource\RelationManagers\AkunFaskesRelationManager;
use App\Filament\Resources\FaskesResource\RelationManagers\AmbulansRelationManager;
use App\Models\Faskes;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaskesResource extends Resource
{
    protected static ?string $model = Faskes::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | \UnitEnum | null $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel = 'Fasilitas Kesehatan';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'Faskes';
    protected static ?string $pluralModelLabel = 'Faskes';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Faskes')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('tipe')
                ->label('Tipe')
                ->options([
                    'rumah_sakit' => 'Rumah Sakit',
                    'puskesmas' => 'Puskesmas',
                    'apotek' => 'Apotek',
                ])
                ->required(),
            Forms\Components\Select::make('wilayah_id')
                ->label('Wilayah')
                ->relationship('wilayah', 'nama')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('admin_id')
                ->label('Admin Faskes')
                ->relationship('admin', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Textarea::make('alamat')
                ->label('Alamat')
                ->required()
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('nomor_telepon')
                ->label('Nomor Telepon')
                ->tel()
                ->nullable(),
            Forms\Components\TextInput::make('jam_operasional')
                ->label('Jam Operasional')
                ->placeholder('08:00 - 17:00')
                ->nullable(),
            Map::make('location')
                ->label('Lokasi (Peta)')
                ->columnSpanFull()
                ->defaultLocation(latitude: -3.6954, longitude: 128.1814)
                ->draggable()
                ->clickable(true)
                ->zoom(13)
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
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'danger' => 'rumah_sakit',
                        'warning' => 'puskesmas',
                        'success' => 'apotek',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rumah_sakit' => 'Rumah Sakit',
                        'puskesmas' => 'Puskesmas',
                        'apotek' => 'Apotek',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_telepon')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jam_operasional')
                    ->label('Jam Operasional'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'rumah_sakit' => 'Rumah Sakit',
                        'puskesmas' => 'Puskesmas',
                        'apotek' => 'Apotek',
                    ]),
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

    public static function getRelationManagers(): array
    {
        return [
            AmbulansRelationManager::class,
            AkunFaskesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaskes::route('/'),
            'create' => Pages\CreateFaskes::route('/create'),
            'edit' => Pages\EditFaskes::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'nomor_telepon'];
    }
}
