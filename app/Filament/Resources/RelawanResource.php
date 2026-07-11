<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RelawanResource\Pages;
use App\Models\Relawan;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class RelawanResource extends Resource
{
    protected static ?string $model = Relawan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hand-raised';
    protected static string | \UnitEnum | null $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel = 'Relawan';
    protected static ?int $navigationSort = 13;
    protected static ?string $modelLabel = 'Relawan';
    protected static ?string $pluralModelLabel = 'Relawan';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('pengguna_id')
                ->label('Pengguna (Mobile App)')
                ->relationship('pengguna', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('umur')
                ->label('Umur')
                ->numeric()
                ->minValue(17)
                ->maxValue(80)
                ->suffix('tahun')
                ->required(),
            Forms\Components\Textarea::make('alamat')
                ->label('Alamat')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('keahlian')
                ->label('Keahlian Khusus')
                ->maxLength(255)
                ->nullable(),
            Forms\Components\TextInput::make('organisasi')
                ->label('Organisasi')
                ->maxLength(255)
                ->nullable(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\Select::make('approved_by')
                ->label('Disetujui Oleh')
                ->relationship('approver', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pengguna.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('umur')
                    ->label('Umur')
                    ->suffix(' tahun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('keahlian')
                    ->label('Keahlian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organisasi')
                    ->label('Organisasi')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Reviewer')
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Relawan $record): bool => $record->status === 'pending')
                    ->action(function (Relawan $record): void {
                        $record->update([
                            'status' => 'disetujui',
                            'approved_by' => Auth::id(),
                        ]);
                        Notification::make()
                            ->title('Relawan disetujui')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Relawan $record): bool => $record->status === 'pending')
                    ->action(function (Relawan $record): void {
                        $record->update([
                            'status' => 'ditolak',
                            'approved_by' => Auth::id(),
                        ]);
                        Notification::make()
                            ->title('Relawan ditolak')
                            ->danger()
                            ->send();
                    }),
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
            'index' => Pages\ListRelawans::route('/'),
            'create' => Pages\CreateRelawan::route('/create'),
            'edit' => Pages\EditRelawan::route('/{record}/edit'),
        ];
    }
}
