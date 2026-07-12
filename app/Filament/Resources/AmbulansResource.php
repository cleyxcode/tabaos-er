<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbulansResource\Pages;
use App\Models\Ambulans;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmbulansResource extends Resource
{
    protected static ?string $model = Ambulans::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';
    protected static string | \UnitEnum | null $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel = 'Ambulans';
    protected static ?int $navigationSort = 11;
    protected static ?string $modelLabel = 'Ambulans';
    protected static ?string $pluralModelLabel = 'Ambulans';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('faskes_id')
                ->label('Faskes')
                ->relationship('faskes', 'nama')
                ->searchable()
                ->preload()
                ->required(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('faskes.nama')
                    ->label('Faskes')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_layanan')
                    ->label('Nama Layanan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nomor_telepon')
                    ->label('Telepon'),
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
                    ->label('Status')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'tidak_tersedia' => 'Tidak Tersedia',
                    ]),
                Tables\Filters\SelectFilter::make('jenis_layanan')
                    ->label('Jenis Layanan')
                    ->options([
                        'gratis' => 'Gratis',
                        'berbayar' => 'Berbayar',
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
            'index' => Pages\ListAmbulans::route('/'),
            'create' => Pages\CreateAmbulans::route('/create'),
            'edit' => Pages\EditAmbulans::route('/{record}/edit'),
        ];
    }
}
