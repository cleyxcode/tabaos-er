<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggunaResource\Pages;
use App\Models\Pengguna;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PenggunaResource extends Resource
{
    protected static ?string $model = Pengguna::class;
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-users';
    protected static string|\UnitEnum|null   $navigationGroup = 'Direktori';
    protected static ?string $navigationLabel  = 'Warga (Mobile App)';
    protected static ?int    $navigationSort   = 14;
    protected static ?string $modelLabel       = 'Pengguna (Warga)';
    protected static ?string $pluralModelLabel = 'Pengguna (Warga)';

    // ── Form (Create & Edit) ──────────────────────────────────────────────────
    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Data Pribadi')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->required()
                        ->unique(table: 'pengguna', ignorable: fn ($record) => $record)
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->nullable()
                        ->unique(table: 'pengguna', ignorable: fn ($record) => $record)
                        ->maxLength(255),
                ])->columns(2),

            Section::make('Password')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Kosongkan jika tidak ingin mengubah password.')
                        ->minLength(8)
                        ->maxLength(255),
                ])->columns(1),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor tersalin'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('relawan.status')
                    ->label('Status Relawan')
                    ->colors([
                        'success' => 'disetujui',
                        'warning' => 'pending',
                        'danger'  => 'ditolak',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'disetujui' => 'Relawan',
                        'pending'   => 'Menunggu',
                        'ditolak'   => 'Ditolak',
                        default     => 'Warga',
                    }),

                Tables\Columns\TextColumn::make('laporan_count')
                    ->label('Jml Laporan')
                    ->counts('laporan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_email')
                    ->label('Punya Email')
                    ->query(fn ($query) => $query->whereNotNull('email')),

                Tables\Filters\Filter::make('is_relawan')
                    ->label('Sudah Daftar Relawan')
                    ->query(fn ($query) => $query->whereHas('relawan')),
            ])
            ->actions([
                // ── Lihat detail ──────────────────────────
                Action::make('view_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Pengguna $record) => 'Detail: ' . $record->name)
                    ->modalContent(fn (Pengguna $record) => view(
                        'filament.modals.pengguna-detail',
                        ['pengguna' => $record->load('relawan', 'laporan')]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // ── Edit data ──────────────────────────────
                EditAction::make()->label('Edit'),

                // ── Reset password ─────────────────────────
                Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation(false)
                    ->form([
                        Forms\Components\TextInput::make('password_baru')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Minimal 8 karakter.'),

                        Forms\Components\TextInput::make('password_konfirmasi')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required()
                            ->same('password_baru')
                            ->maxLength(255),
                    ])
                    ->action(function (Pengguna $record, array $data): void {
                        $record->update(['password' => bcrypt($data['password_baru'])]);
                        // Cabut semua token aktif agar user login ulang
                        $record->tokens()->delete();
                        Notification::make()
                            ->title('Password berhasil direset')
                            ->body("Password {$record->name} telah diperbarui. Semua sesi aktif dicabut.")
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Reset Password Pengguna')
                    ->modalSubmitActionLabel('Simpan Password'),

                // ── Hapus ──────────────────────────────────
                DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPenggunas::route('/'),
            'create' => Pages\CreatePengguna::route('/create'),
            'edit'   => Pages\EditPengguna::route('/{record}/edit'),
        ];
    }
}
