<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkunFaskesResource\Pages;
use App\Models\AkunFaskes;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AkunFaskesResource extends Resource
{
    protected static ?string $model = AkunFaskes::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null   $navigationGroup = 'Manajemen Akun';
    protected static ?string $navigationLabel  = 'Akun Faskes';
    protected static ?int    $navigationSort   = 11;
    protected static ?string $modelLabel       = 'Akun Faskes';
    protected static ?string $pluralModelLabel = 'Akun Faskes';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Data Akun')
                ->schema([
                    Forms\Components\Select::make('faskes_id')
                        ->label('Fasilitas Kesehatan')
                        ->relationship('faskes', 'nama')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('nama_petugas')
                        ->label('Nama Petugas')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Akun')
                        ->email()
                        ->required()
                        ->unique(table: 'akun_faskes', ignorable: fn ($record) => $record)
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

            \Filament\Schemas\Components\Section::make('Status')
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
                Tables\Columns\TextColumn::make('nama_petugas')
                    ->label('Nama Petugas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('faskes.nama')
                    ->label('Faskes')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('faskes.tipe')
                    ->label('Tipe Faskes')
                    ->colors([
                        'info'    => 'rumah_sakit',
                        'success' => 'puskesmas',
                        'warning' => 'apotek',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'rumah_sakit' => 'Rumah Sakit',
                        'puskesmas'   => 'Puskesmas',
                        'apotek'      => 'Apotek',
                        default       => ucfirst($state ?? '-'),
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email Akun')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aktif',
                        'danger'  => 'nonaktif',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),

                Tables\Filters\SelectFilter::make('faskes_tipe')
                    ->label('Tipe Faskes')
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->whereHas('faskes', fn ($q) => $q->where('tipe', $data['value']))
                        : $query)
                    ->options([
                        'rumah_sakit' => 'Rumah Sakit',
                        'puskesmas'   => 'Puskesmas',
                        'apotek'      => 'Apotek',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('nonaktifkan')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AkunFaskes $record): bool => $record->status === 'aktif')
                    ->action(function (AkunFaskes $record): void {
                        $record->update(['status' => 'nonaktif']);
                        Notification::make()->title('Akun berhasil dinonaktifkan')->success()->send();
                    }),

                \Filament\Actions\Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AkunFaskes $record): bool => $record->status === 'nonaktif')
                    ->action(function (AkunFaskes $record): void {
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
                    ->action(function (AkunFaskes $record, array $data): void {
                        $record->update(['password' => bcrypt($data['password_baru'])]);
                        Notification::make()->title('Password berhasil direset')->success()->send();
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
            'index'  => Pages\ListAkunFaskes::route('/'),
            'create' => Pages\CreateAkunFaskes::route('/create'),
            'edit'   => Pages\EditAkunFaskes::route('/{record}/edit'),
        ];
    }
}
