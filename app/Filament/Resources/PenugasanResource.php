<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenugasanResource\Pages;
use App\Models\Ambulans;
use App\Models\Penugasan;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PenugasanResource extends Resource
{
    protected static ?string $model = Penugasan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Penanganan Bencana';
    protected static ?string $navigationLabel = 'Penugasan';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Penugasan';
    protected static ?string $pluralModelLabel = 'Penugasan';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('laporan_id')
                ->label('Laporan Bencana')
                ->relationship('laporan', 'nama_pelapor')
                ->searchable()
                ->preload()
                ->required(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('laporan.nama_pelapor')
                    ->label('Laporan')
                    ->searchable()
                    ->sortable(),
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
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ditugaskan' => 'Ditugaskan',
                        'dalam_perjalanan' => 'Dalam Perjalanan',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
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
            'index' => Pages\ListPenugasan::route('/'),
            'create' => Pages\CreatePenugasan::route('/create'),
            'edit' => Pages\EditPenugasan::route('/{record}/edit'),
        ];
    }
}
