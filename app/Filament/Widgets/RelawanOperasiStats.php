<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\PetaRealtimePage;
use App\Filament\Resources\AkunRelawanResource;
use App\Filament\Resources\RelawanResource;
use App\Services\DashboardStatistikService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class RelawanOperasiStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Relawan & Operasi Lapangan';

    protected ?string $description = 'Kesiapan relawan, penugasan, dan pelacakan lokasi';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = DashboardStatistikService::forFilters($this->pageFilters);
        $stats = $service->relawanOperasi();

        return [
            Stat::make('Relawan Disetujui', $stats['disetujui'])
                ->description("{$stats['pending']} menunggu persetujuan · {$stats['ditolak']} ditolak")
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->icon('heroicon-o-heart')
                ->color('success')
                ->chart($service->sparklineHarian(7))
                ->url(RelawanResource::getUrl('index')),

            Stat::make('Akun Relawan Aktif', $stats['akun_aktif'])
                ->description("{$stats['akun_nonaktif']} akun nonaktif")
                ->descriptionIcon('heroicon-m-identification', IconPosition::Before)
                ->icon('heroicon-o-identification')
                ->color('info')
                ->url(AkunRelawanResource::getUrl('index')),

            Stat::make('Relawan Online', $stats['lokasi_aktif'])
                ->description('Update lokasi ≤ 30 menit terakhir')
                ->descriptionIcon('heroicon-m-map-pin', IconPosition::Before)
                ->icon('heroicon-o-signal')
                ->color('primary')
                ->url(PetaRealtimePage::getUrl()),

            Stat::make('Penugasan Aktif', $stats['penugasan_aktif'])
                ->description("{$stats['penugasan_selesai']} penugasan selesai")
                ->descriptionIcon('heroicon-m-clipboard-document-check', IconPosition::Before)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),

            Stat::make('Laporan Diklaim', $stats['laporan_diklaim'])
                ->description("{$stats['total']} total relawan terdaftar")
                ->descriptionIcon('heroicon-m-hand-raised', IconPosition::Before)
                ->icon('heroicon-o-hand-raised')
                ->color('danger'),
        ];
    }
}
