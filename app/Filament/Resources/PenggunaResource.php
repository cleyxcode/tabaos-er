<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggunaResource\Pages;
use App\Models\Pengguna;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PenggunaResource extends Resource
{
    protected static ?string $model = Pengguna::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel = 'Warga (Mobile App)';
    protected static ?int $navigationSort = 14;
    protected static ?string $modelLabel = 'Pengguna (Warga)';
    protected static ?string $pluralModelLabel = 'Pengguna (Warga)';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            // Form is empty as this is read-only
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Only view action for monitoring
            ])
            ->bulkActions([
                // No bulk actions needed for read-only
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenggunas::route('/'),
        ];
    }
}
