<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\LaporanBencanaResource;
use App\Models\LaporanBencana;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class LaporanTerbaruTable extends TableWidget
{
    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Laporan Bencana Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LaporanBencana::query()
                    ->with(['wilayah', 'relawanDitugaskan.relawan.pengguna'])
                    ->latest(),
            )
            ->heading('Laporan Bencana Terbaru')
            ->description('10 laporan terakhir — klik baris untuk detail')
            ->poll('30s')
            ->paginated([10])
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Masuk')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_pelapor')
                    ->label('Pelapor')
                    ->searchable()
                    ->limit(24),
                Tables\Columns\TextColumn::make('jenis_kejadian')
                    ->label('Kejadian')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'diverifikasi' => 'Diverifikasi',
                        'ditangani' => 'Ditangani',
                        'selesai' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'diverifikasi' => 'info',
                        'ditangani' => 'primary',
                        'selesai' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status_penanganan')
                    ->label('Penanganan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_ditangani' => 'Belum',
                        'sedang_ditangani' => 'Proses',
                        'selesai_ditangani' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'belum_ditangani' => 'danger',
                        'sedang_ditangani' => 'warning',
                        'selesai_ditangani' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('relawanDitugaskan.relawan.pengguna.name')
                    ->label('Relawan')
                    ->placeholder('Belum ditugaskan')
                    ->limit(20),
                Tables\Columns\TextColumn::make('korban_ringkas')
                    ->label('Korban')
                    ->state(fn (LaporanBencana $record): string => collect([
                        $record->meninggal_jumlah > 0 ? "M: {$record->meninggal_jumlah}" : null,
                        $record->hilang_jumlah > 0 ? "H: {$record->hilang_jumlah}" : null,
                        $record->luka_berat_jumlah > 0 ? "LB: {$record->luka_berat_jumlah}" : null,
                        $record->luka_ringan_jumlah > 0 ? "LR: {$record->luka_ringan_jumlah}" : null,
                    ])->filter()->implode(' · ') ?: '-'),
            ])
            ->recordUrl(fn (LaporanBencana $record): string => LaporanBencanaResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                Action::make('lihatSemua')
                    ->label('Semua Laporan')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(LaporanBencanaResource::getUrl('index')),
            ])
            ->emptyStateHeading('Belum ada laporan')
            ->emptyStateDescription('Laporan dari masyarakat akan muncul di sini.');
    }
}
