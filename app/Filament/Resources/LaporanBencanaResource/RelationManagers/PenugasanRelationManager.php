<?php

namespace App\Filament\Resources\LaporanBencanaResource\RelationManagers;

use App\Models\Ambulans;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PenugasanRelationManager extends RelationManager
{
    protected static string $relationship = 'penugasan';
    protected static ?string $title = 'Penugasan';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('relawan_id')
                ->label('Relawan')
                ->relationship('relawan.pengguna', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('petugas_id')
                ->label('Petugas')
                ->relationship('petugas', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('ambulans_id')
                ->label('Ambulans (Tersedia)')
                ->options(fn () => Ambulans::where('status', 'tersedia')->get()->pluck('nama_layanan', 'id'))
                ->searchable()
                ->nullable(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'ditugaskan' => 'Ditugaskan',
                    'dalam_perjalanan' => 'Dalam Perjalanan',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan',
                ])
                ->default('ditugaskan')
                ->live()
                ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set): void {
                    if ($state === 'selesai') {
                        $set('selesai_at', now()->toDateTimeString());
                    }
                })
                ->required(),
            Forms\Components\Textarea::make('catatan')
                ->label('Catatan')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('ditugaskan_at')
                ->label('Ditugaskan Pada')
                ->nullable(),
            Forms\Components\DateTimePicker::make('selesai_at')
                ->label('Selesai Pada')
                ->nullable(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('relawan.pengguna.name')
                    ->label('Relawan')
                    ->default('-'),
                Tables\Columns\TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->default('-'),
                Tables\Columns\TextColumn::make('ambulans.nama_layanan')
                    ->label('Ambulans')
                    ->default('-'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'ditugaskan',
                        'warning' => 'dalam_perjalanan',
                        'success' => 'selesai',
                        'danger' => 'dibatalkan',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ditugaskan' => 'Ditugaskan',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('ditugaskan_at')
                    ->label('Ditugaskan')
                    ->dateTime('d M Y H:i'),
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
