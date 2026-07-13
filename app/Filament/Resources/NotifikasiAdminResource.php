<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\DTO\KirimNotifikasiAdminDTO;
use App\Filament\Resources\NotifikasiAdminResource\Pages;
use App\Models\AkunRelawan;
use App\Models\NotifikasiAdmin;
use App\Services\AdminNotifikasiService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class NotifikasiAdminResource extends Resource
{
    protected static ?string $model = NotifikasiAdmin::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';

    protected static ?string $navigationLabel = 'Pesan ke Relawan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pesan Admin';

    protected static ?string $pluralModelLabel = 'Pesan Admin';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Isi Pesan')
                ->description('Pesan akan langsung dikirim ke akun relawan yang dipilih.')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    Forms\Components\TextInput::make('judul')
                        ->label('Judul / Subjek')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('pesan')
                        ->label('Pesan')
                        ->required()
                        ->rows(5)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('gambar')
                        ->label('Gambar (Opsional)')
                        ->image()
                        ->disk('public')
                        ->directory('notifikasi-admin')
                        ->maxSize(5120)
                        ->helperText('Format JPG/PNG, maks. 5 MB. Akan ditampilkan di app Flutter.')
                        ->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('Penerima Relawan')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Toggle::make('kirim_ke_relawan')
                        ->label('Kirim ke Relawan')
                        ->default(true)
                        ->live(),

                    Forms\Components\Toggle::make('kirim_semua_relawan')
                        ->label('Semua akun relawan aktif')
                        ->default(true)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('kirim_ke_relawan'))
                        ->live(),

                    Forms\Components\Select::make('akun_relawan_ids')
                        ->label('Pilih akun relawan tertentu')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => AkunRelawan::query()
                            ->where('status', 'aktif')
                            ->with('relawan.pengguna')
                            ->orderBy('email')
                            ->get()
                            ->mapWithKeys(fn (AkunRelawan $akun): array => [
                                $akun->id => self::labelAkunRelawan($akun),
                            ])
                            ->all())
                        ->helperText('Kosongkan opsi "Semua" lalu pilih satu atau lebih akun relawan.')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('kirim_ke_relawan') && ! $get('kirim_semua_relawan'))
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('kirim_ke_relawan') && ! $get('kirim_semua_relawan'))
                        ->columnSpanFull(),
                ])->columns(1),

            \Filament\Schemas\Components\Section::make('Metadata')
                ->schema([
                    Forms\Components\Hidden::make('admin_id')
                        ->default(fn () => Auth::id()),

                    Forms\Components\Hidden::make('status')
                        ->default('draft'),
                ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Dikirim Oleh')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cakupan_relawan')
                    ->label('Relawan')
                    ->state(fn (NotifikasiAdmin $record): string => ! $record->kirim_ke_relawan
                        ? '-'
                        : ($record->kirim_semua_relawan ? 'Semua' : 'Tertentu')),

                Tables\Columns\TextColumn::make('jumlah_penerima')
                    ->label('Penerima')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'terkirim',
                        'danger' => 'gagal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'terkirim' => 'Terkirim',
                        'gagal' => 'Gagal',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('dikirim_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'terkirim' => 'Terkirim',
                        'gagal' => 'Gagal',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifikasiAdmin::route('/'),
            'create' => Pages\CreateNotifikasiAdmin::route('/create'),
            'view' => Pages\ViewNotifikasiAdmin::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function kirimRecord(NotifikasiAdmin $record): NotifikasiAdmin
    {
        return app(AdminNotifikasiService::class)->kirim($record);
    }

    public static function buatDanKirim(array $data): NotifikasiAdmin
    {
        $gambar = $data['gambar'] ?? null;
        if (is_array($gambar)) {
            $gambar = $gambar[0] ?? null;
        }

        $dto = new KirimNotifikasiAdminDTO(
            adminId: (int) $data['admin_id'],
            judul: $data['judul'],
            pesan: $data['pesan'],
            gambar: $gambar,
            kirimKeRelawan: (bool) ($data['kirim_ke_relawan'] ?? false),
            kirimSemuaRelawan: (bool) ($data['kirim_semua_relawan'] ?? true),
            akunRelawanIds: array_map('intval', $data['akun_relawan_ids'] ?? []),
        );

        return app(AdminNotifikasiService::class)->buatDanKirim($dto);
    }

    public static function labelAkunRelawan(AkunRelawan $akun): string
    {
        $nama = $akun->relawan?->pengguna?->name ?? 'Relawan';

        return "{$nama} — {$akun->email}";
    }
}
