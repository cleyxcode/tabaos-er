<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanBencanaResource\Pages;
use App\Filament\Resources\LaporanBencanaResource\RelationManagers\PenugasanRelationManager;
use App\Models\LaporanBencana;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Dotswan\MapPicker\Fields\Map;

class LaporanBencanaResource extends Resource
{
    protected static ?string $model = LaporanBencana::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static string | \UnitEnum | null $navigationGroup = 'Penanganan Bencana';
    protected static ?string $navigationLabel = 'Laporan Bencana';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Laporan Bencana';
    protected static ?string $pluralModelLabel = 'Laporan Bencana';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Info Pelapor')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('pengguna_id')
                        ->label('Pengguna (Pelapor)')
                        ->relationship('pengguna', 'name')
                        ->searchable()
                        ->nullable()
                        ->preload(),
                    Forms\Components\TextInput::make('nama_pelapor')
                        ->label('Nama Pelapor')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nomor_kontak')
                        ->label('Nomor Kontak')
                        ->required()
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\Select::make('jenis_kejadian')
                        ->label('Jenis Kejadian')
                        ->options([
                            'Gempa Bumi' => 'Gempa Bumi',
                            'Tsunami' => 'Tsunami',
                            'Tanah Longsor' => 'Tanah Longsor',
                            'Kebakaran' => 'Kebakaran',
                            'Banjir' => 'Banjir',
                            'Lainnya' => 'Lainnya',
                        ])
                        ->searchable()
                        ->required(),
                    Forms\Components\Toggle::make('di_lokasi_kejadian')
                        ->label('Ya, saya di lokasi kejadian')
                        ->default(true),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Lokasi Kejadian')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\Select::make('wilayah_id')
                        ->label('Wilayah')
                        ->relationship('wilayah', 'nama')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Textarea::make('alamat_lokasi')
                        ->label('Alamat Lokasi')
                        ->rows(2)
                        ->nullable(),
                    Map::make('location')
                        ->label('Pinpoint Lokasi (Peta)')
                        ->columnSpanFull()
                        ->defaultLocation(latitude: -3.6954, longitude: 128.1814)
                        ->draggable()
                        ->clickable(true)
                        ->zoom(13)
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set): void {
                            $set('latitude', $state['lat'] ?? null);
                            $set('longitude', $state['lng'] ?? null);
                        })
                        ->afterStateHydrated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void {
                            $lat = $get('latitude');
                            $lng = $get('longitude');
                            if ($lat && $lng) {
                                $set('location', ['lat' => $lat, 'lng' => $lng]);
                            }
                        })
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('latitude')
                        ->label('Latitude')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\TextInput::make('longitude')
                        ->label('Longitude')
                        ->numeric()
                        ->nullable(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Waktu & Deskripsi')
                ->icon('heroicon-o-clock')
                ->schema([
                    Forms\Components\DateTimePicker::make('tanggal_kejadian')
                        ->label('Tanggal & Waktu Kejadian')
                        ->required()
                        ->default(now()),
                    Forms\Components\Textarea::make('deskripsi')
                        ->label('Deskripsi Kejadian')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('foto')
                        ->label('Foto Kejadian')
                        ->multiple()
                        ->image()
                        ->disk('public')
                        ->directory('laporan-foto')
                        ->maxFiles(10)
                        ->nullable()
                        ->columnSpanFull(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Data Korban')
                ->icon('heroicon-o-heart')
                ->schema([
                    // Meninggal
                    \Filament\Schemas\Components\Fieldset::make('Meninggal')
                        ->schema([
                            Forms\Components\TextInput::make('meninggal_jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\Select::make('meninggal_jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan', 'Campuran' => 'Campuran'])
                                ->nullable(),
                            Forms\Components\Textarea::make('penyebab_meninggal')
                                ->label('Penyebab')
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull(),
                        ])->columns(2),

                    // Hilang
                    \Filament\Schemas\Components\Fieldset::make('Hilang')
                        ->schema([
                            Forms\Components\TextInput::make('hilang_jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\Select::make('hilang_jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan', 'Campuran' => 'Campuran'])
                                ->nullable(),
                        ])->columns(2),

                    // Luka Berat
                    \Filament\Schemas\Components\Fieldset::make('Luka Berat')
                        ->schema([
                            Forms\Components\TextInput::make('luka_berat_jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\Select::make('luka_berat_jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan', 'Campuran' => 'Campuran'])
                                ->nullable(),
                            Forms\Components\Textarea::make('penyebab_luka_berat')
                                ->label('Penyebab')
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull(),
                        ])->columns(2),

                    // Luka Ringan
                    \Filament\Schemas\Components\Fieldset::make('Luka Ringan')
                        ->schema([
                            Forms\Components\TextInput::make('luka_ringan_jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\Select::make('luka_ringan_jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan', 'Campuran' => 'Campuran'])
                                ->nullable(),
                            Forms\Components\Textarea::make('penyebab_luka_ringan')
                                ->label('Penyebab')
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull(),
                        ])->columns(2),
                ]),

            \Filament\Schemas\Components\Section::make('Status Verifikasi')
                ->icon('heroicon-o-check-badge')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'diverifikasi' => 'Diverifikasi',
                            'ditangani' => 'Ditangani',
                            'selesai' => 'Selesai',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\Select::make('verified_by')
                        ->label('Diverifikasi Oleh')
                        ->relationship('verifikator', 'name')
                        ->searchable()
                        ->nullable()
                        ->preload(),
                    Forms\Components\DateTimePicker::make('verified_at')
                        ->label('Waktu Verifikasi')
                        ->nullable(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pelapor')
                    ->label('Pelapor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_kejadian')
                    ->label('Jenis Kejadian')
                    ->searchable()
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_kejadian')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'diverifikasi',
                        'primary' => 'ditangani',
                        'success' => 'selesai',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'diverifikasi' => 'Diverifikasi',
                        'ditangani' => 'Ditangani',
                        'selesai' => 'Selesai',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('meninggal_jumlah')
                    ->label('Meninggal')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('luka_berat_jumlah')
                    ->label('Luka Berat')
                    ->sortable()
                    ->alignCenter(),
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
                    ->options([
                        'pending' => 'Pending',
                        'diverifikasi' => 'Diverifikasi',
                        'ditangani' => 'Ditangani',
                        'selesai' => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('wilayah_id')
                    ->label('Wilayah')
                    ->relationship('wilayah', 'nama'),
            ])
            ->actions([
                \Filament\Actions\Action::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LaporanBencana $record): bool => $record->status === 'pending')
                    ->action(function (LaporanBencana $record): void {
                        $record->update([
                            'status' => 'diverifikasi',
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Laporan berhasil diverifikasi')
                            ->success()
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

    public static function getRelationManagers(): array
    {
        return [
            PenugasanRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanBencana::route('/'),
            'create' => Pages\CreateLaporanBencana::route('/create'),
            'edit' => Pages\EditLaporanBencana::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_pelapor', 'nomor_kontak', 'jenis_kejadian', 'alamat_lokasi'];
    }
}
