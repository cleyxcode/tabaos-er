<?php

namespace App\Filament\Resources\FaskesResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AmbulansRelationManager extends RelationManager
{
    protected static string $relationship = 'ambulans';
    protected static ?string $title = 'Daftar Ambulans';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_layanan')
                ->label('Nama Layanan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('nomor_telepon')
                ->label('Nomor Telepon')
                ->required()
                ->tel(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'tersedia' => 'Tersedia',
                    'tidak_tersedia' => 'Tidak Tersedia',
                ])
                ->default('tersedia')
                ->required(),
            Forms\Components\Select::make('jenis_layanan')
                ->label('Jenis Layanan')
                ->options([
                    'gratis' => 'Gratis',
                    'berbayar' => 'Berbayar',
                ])
                ->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_layanan')->label('Nama Layanan'),
                Tables\Columns\TextColumn::make('nomor_telepon')->label('Telepon'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'tersedia',
                        'danger' => 'tidak_tersedia',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tersedia' => 'Tersedia',
                        'tidak_tersedia' => 'Tidak Tersedia',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('jenis_layanan')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'gratis',
                        'warning' => 'berbayar',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gratis' => 'Gratis',
                        'berbayar' => 'Berbayar',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'tidak_tersedia' => 'Tidak Tersedia',
                    ]),
            ])
            ->headerActions([\Filament\Actions\CreateAction::make()])
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
