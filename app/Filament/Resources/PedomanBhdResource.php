<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedomanBhdResource\Pages;
use App\Models\PedomanBhd;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PedomanBhdResource extends Resource
{
    protected static ?string $model = PedomanBhd::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Edukasi dan Simulasi';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Edukasi dan Simulasi';

    protected static ?string $pluralModelLabel = 'Edukasi dan Simulasi';

    protected static ?string $slug = 'edukasi';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')
                ->label('Judul')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Select::make('tipe_file')
                ->label('Tipe Materi')
                ->options([
                    'pdf' => 'PDF',
                    'video' => 'Video',
                    'gambar' => 'Foto / Gambar',
                    'dokumen' => 'Dokumen Lain',
                    'aplikasi' => 'Aplikasi Simulasi (APK)',
                ])
                ->required()
                ->live()
                ->helperText('Bisa dipilih manual atau otomatis dari ekstensi file yang diunggah. Pilih Aplikasi Simulasi untuk file APK.'),
            Forms\Components\Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->label(fn (Get $get): string => $get('tipe_file') === 'aplikasi'
                    ? 'File Aplikasi Simulasi (APK)'
                    : 'File Materi')
                ->disk('public')
                ->directory('edukasi')
                ->required()
                ->downloadable()
                ->openable()
                ->maxSize(204800)
                ->acceptedFileTypes([
                    'application/pdf',
                    'video/mp4',
                    'video/webm',
                    'video/quicktime',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.android.package-archive',
                    'application/octet-stream',
                ])
                ->helperText('Unggah foto, PDF, video, atau APK aplikasi simulasi (maks. 200 MB). APK bisa diunduh di aplikasi mobile.')
                ->afterStateUpdated(function ($state, Set $set): void {
                    if (! is_string($state) || $state === '') {
                        return;
                    }

                    $ext = strtolower(pathinfo($state, PATHINFO_EXTENSION));
                    $tipe = match ($ext) {
                        'pdf' => 'pdf',
                        'mp4', 'webm', 'mov', 'mkv' => 'video',
                        'jpg', 'jpeg', 'png', 'webp', 'gif' => 'gambar',
                        'apk' => 'aplikasi',
                        default => 'dokumen',
                    };
                    $set('tipe_file', $tipe);
                })
                ->columnSpanFull(),
            Forms\Components\Hidden::make('uploaded_by')
                ->default(fn () => Auth::id()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tipe_file')
                    ->label('Tipe')
                    ->colors([
                        'danger' => 'pdf',
                        'primary' => 'video',
                        'success' => 'gambar',
                        'warning' => 'dokumen',
                        'info' => 'aplikasi',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gambar' => 'Foto',
                        'pdf' => 'PDF',
                        'video' => 'Video',
                        'dokumen' => 'Dokumen',
                        'aplikasi' => 'Aplikasi Simulasi',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('pengunggah.name')
                    ->label('Diunggah Oleh')
                    ->default('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diunggah Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe_file')
                    ->label('Tipe Materi')
                    ->options([
                        'pdf' => 'PDF',
                        'video' => 'Video',
                        'gambar' => 'Foto / Gambar',
                        'dokumen' => 'Dokumen Lain',
                        'aplikasi' => 'Aplikasi Simulasi (APK)',
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
            'index' => Pages\ListPedomanBhds::route('/'),
            'create' => Pages\CreatePedomanBhd::route('/create'),
            'edit' => Pages\EditPedomanBhd::route('/{record}/edit'),
        ];
    }
}
