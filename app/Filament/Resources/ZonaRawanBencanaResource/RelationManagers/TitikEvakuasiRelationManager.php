<?php

namespace App\Filament\Resources\ZonaRawanBencanaResource\RelationManagers;

use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TitikEvakuasiRelationManager extends RelationManager
{
    protected static string $relationship = 'titikEvakuasi';
    protected static ?string $title = 'Titik Evakuasi';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Titik Evakuasi')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('kapasitas')
                ->label('Kapasitas (Orang)')
                ->numeric()
                ->nullable(),
            Forms\Components\Textarea::make('fasilitas')
                ->label('Fasilitas Tersedia')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fasilitas')
                    ->label('Fasilitas')
                    ->limit(50),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
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
}
