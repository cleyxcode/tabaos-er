<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkunRelawanResource\Pages;
use App\Models\AkunRelawan;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AkunRelawanResource extends Resource
{
    protected static ?string $model = AkunRelawan::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-identification';
    protected static string|\UnitEnum|null   $navigationGroup = 'Manajemen Akun';
    protected static ?string $navigationLabel    = 'Akun Relawan';
    protected static ?int    $navigationSort     = 10;
    protected static ?string $modelLabel         = 'Akun Relawan';
    protected static ?string $pluralModelLabel   = 'Akun Relawan';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Data Akun')
                ->schema([
                    Forms\Components\Select::make('relawan_id')
                        ->label('Relawan')
                        ->relationship(
                            'relawan',
                            'id',
                            fn ($query) => $query
                                ->where('status', 'disetujui')
                                ->whereDoesntHave('akunRelawan'),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => ($record->pengguna?->name ?? 'Tanpa Nama')
                                . ' — ' . ($record->keahlian ?? '-')
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Hanya relawan berstatus disetujui dan belum memiliki akun yang muncul di sini.')
                        ->disabled(fn (string $operation) => $operation === 'edit'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Akun')
                        ->email()
                        ->required()
                        ->unique(table: 'akun_relawan', ignorable: fn ($record) => $record)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Kosongkan jika tidak ingin mengubah password.')
                        ->maxLength(255),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Status Akun')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                        ->required()
                        ->default('aktif'),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('relawan.pengguna.name')
                    ->label('Nama Relawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('relawan.keahlian')
                    ->label('Keahlian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email Akun')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status Akun')
                    ->colors([
                        'success' => 'aktif',
                        'danger'  => 'nonaktif',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\BadgeColumn::make('relawan.status')
                    ->label('Status Relawan')
                    ->colors([
                        'success' => 'disetujui',
                        'warning' => 'pending',
                        'danger'  => 'ditolak',
                    ])
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '-')),

                Tables\Columns\TextColumn::make('lokasi_updated_at')
                    ->label('Lokasi Terakhir Update')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Belum pernah'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Akun')
                    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),

                Tables\Filters\SelectFilter::make('relawan_status')
                    ->label('Status Relawan')
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->whereHas('relawan', fn ($q) => $q->where('status', $data['value']))
                        : $query)
                    ->options([
                        'disetujui' => 'Disetujui',
                        'pending'   => 'Pending',
                        'ditolak'   => 'Ditolak',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('nonaktifkan')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AkunRelawan $record): bool => $record->status === 'aktif')
                    ->action(function (AkunRelawan $record): void {
                        $record->update(['status' => 'nonaktif']);
                        Notification::make()->title('Akun berhasil dinonaktifkan')->success()->send();
                    }),

                \Filament\Actions\Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AkunRelawan $record): bool => $record->status === 'nonaktif')
                    ->action(function (AkunRelawan $record): void {
                        $record->update(['status' => 'aktif']);
                        Notification::make()->title('Akun berhasil diaktifkan')->success()->send();
                    }),

                \Filament\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password_baru')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->action(function (AkunRelawan $record, array $data): void {
                        $record->update(['password' => bcrypt($data['password_baru'])]);
                        Notification::make()->title('Password berhasil direset')->success()->send();
                    }),

                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->modalDescription('Akun relawan akan dihapus permanen. Laporan bencana yang sedang ditangani relawan ini akan dilepas penugasannya.'),
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
            'index'  => Pages\ListAkunRelawans::route('/'),
            'create' => Pages\CreateAkunRelawan::route('/create'),
            'edit'   => Pages\EditAkunRelawan::route('/{record}/edit'),
        ];
    }
}
