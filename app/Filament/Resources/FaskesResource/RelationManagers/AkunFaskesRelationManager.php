<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaskesResource\RelationManagers;

use App\Models\AkunFaskes;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class AkunFaskesRelationManager extends RelationManager
{
    protected static string $relationship = 'akunFaskes';

    protected static ?string $title = 'Akun Login Aplikasi';

    public function form(Schema $form): Schema
    {
        return $form->schema([
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

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                ->required()
                ->default('aktif'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_petugas')
                    ->label('Nama Petugas')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email Akun')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'nonaktif',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['faskes_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
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
}
