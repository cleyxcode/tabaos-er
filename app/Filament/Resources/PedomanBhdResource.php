<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedomanBhdResource\Pages;
use App\Models\PedomanBhd;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PedomanBhdResource extends Resource
{
    protected static ?string $model = PedomanBhd::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static string | \UnitEnum | null $navigationGroup = 'Konten';
    protected static ?string $navigationLabel = 'Pedoman BHD';
    protected static ?int $navigationSort = 40;
    protected static ?string $modelLabel = 'Pedoman BHD';
    protected static ?string $pluralModelLabel = 'Pedoman BHD';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')
                ->label('Judul Pedoman')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('tipe_file')
                ->label('Tipe File')
                ->options([
                    'pdf' => 'PDF',
                    'video' => 'Video',
                    'gambar' => 'Gambar',
                    'dokumen' => 'Dokumen',
                ])
                ->required(),
            Forms\Components\Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->label('File')
                ->disk('public')
                ->directory('pedoman-bhd')
                ->required()
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
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
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
                    ->label('Tipe File')
                    ->options([
                        'pdf' => 'PDF',
                        'video' => 'Video',
                        'gambar' => 'Gambar',
                        'dokumen' => 'Dokumen',
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
