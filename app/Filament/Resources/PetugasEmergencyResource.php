<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PetugasEmergencyResource\Pages;
use App\Models\PetugasEmergency;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PetugasEmergencyResource extends Resource
{
    protected static ?string $model = PetugasEmergency::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel = 'Petugas Emergency';
    protected static ?int $navigationSort = 12;
    protected static ?string $modelLabel = 'Petugas Emergency';
    protected static ?string $pluralModelLabel = 'Petugas Emergency';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Akun User (Opsional)')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\TextInput::make('nama')
                ->label('Nama Petugas')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('kategori')
                ->label('Kategori')
                ->options([
                    'medis' => 'Medis',
                    'sar' => 'SAR',
                    'logistik' => 'Logistik',
                    'lainnya' => 'Lainnya',
                ])
                ->required(),
            Forms\Components\TextInput::make('nomor_telepon')
                ->label('Nomor Telepon')
                ->required()
                ->tel(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'aktif' => 'Aktif',
                    'nonaktif' => 'Nonaktif',
                ])
                ->default('aktif')
                ->required(),
            Forms\Components\Textarea::make('alamat')
                ->label('Alamat')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
            Map::make('location')
                ->label('Lokasi Terakhir (Peta)')
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
                ->nullable(),
            Forms\Components\TextInput::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->nullable(),
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
                Tables\Columns\BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->colors([
                        'danger' => 'medis',
                        'warning' => 'sar',
                        'primary' => 'logistik',
                        'secondary' => 'lainnya',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('nomor_telepon')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'nonaktif',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'medis' => 'Medis',
                        'sar' => 'SAR',
                        'logistik' => 'Logistik',
                        'lainnya' => 'Lainnya',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPetugasEmergencies::route('/'),
            'create' => Pages\CreatePetugasEmergency::route('/create'),
            'edit' => Pages\EditPetugasEmergency::route('/{record}/edit'),
        ];
    }
}
