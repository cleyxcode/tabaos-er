<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\LaporanBencanaResource;
use App\Services\DashboardStatistikService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PenangananDaruratStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Penanganan Darurat';

    protected ?string $description = 'Ringkasan laporan bencana dan status penanganan terkini';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = DashboardStatistikService::forFilters($this->pageFilters);
        $stats = $service->penangananDarurat();

        return [
            Stat::make('Butuh Verifikasi', $stats['pending'])
                ->description("{$stats['hari_ini']} laporan masuk hari ini")
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->chart($service->sparklineHarian(7, fn ($query) => $query->where('status', 'pending')))
                ->url(LaporanBencanaResource::getUrl('index')),

            Stat::make('Sedang Ditangani', $stats['sedang_ditangani'])
                ->description("{$stats['ditangani']} laporan status ditangani")
                ->descriptionIcon('heroicon-m-arrow-path', IconPosition::Before)
                ->icon('heroicon-o-fire')
                ->color('warning')
                ->chart($service->sparklineHarian(7, fn ($query) => $query->where('status_penanganan', 'sedang_ditangani'))),

            Stat::make('Belum Ditangani', $stats['belum_ditangani'])
                ->description("{$stats['diverifikasi']} sudah diverifikasi")
                ->descriptionIcon('heroicon-m-bell-alert', IconPosition::Before)
                ->icon('heroicon-o-bell-alert')
                ->color('info')
                ->chart($service->sparklineHarian(7, fn ($query) => $query->where('status_penanganan', 'belum_ditangani'))),

            Stat::make('Selesai Ditangani', $stats['selesai_ditangani'])
                ->description("{$stats['selesai']} laporan ditutup")
                ->descriptionIcon('heroicon-m-check-badge', IconPosition::Before)
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->chart($service->sparklineHarian(7, fn ($query) => $query->where('status_penanganan', 'selesai_ditangani'))),

            Stat::make('Total Laporan', $stats['total'])
                ->description("{$stats['minggu_ini']} laporan minggu ini · {$stats['aktif']} masih aktif")
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->chart($service->sparklineHarian(7)),
        ];
    }
}
