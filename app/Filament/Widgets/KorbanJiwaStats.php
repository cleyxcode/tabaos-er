<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\LaporanBencanaResource;
use App\Services\DashboardStatistikService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class KorbanJiwaStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Dampak Korban Jiwa';

    protected ?string $description = 'Agregat korban dari seluruh laporan bencana';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = app(DashboardStatistikService::class)->korbanJiwa();

        return [
            Stat::make('Total Korban', number_format($stats['total_korban']))
                ->description("{$stats['laporan_berkorban']} laporan melaporkan korban")
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->icon('heroicon-o-users')
                ->color('danger')
                ->url(LaporanBencanaResource::getUrl('index')),

            Stat::make('Meninggal Dunia', number_format($stats['meninggal']))
                ->description('Korban jiwa')
                ->descriptionIcon('heroicon-m-x-circle', IconPosition::Before)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Hilang', number_format($stats['hilang']))
                ->description('Belum ditemukan')
                ->descriptionIcon('heroicon-m-question-mark-circle', IconPosition::Before)
                ->icon('heroicon-o-question-mark-circle')
                ->color('warning'),

            Stat::make('Luka Berat', number_format($stats['luka_berat']))
                ->description('Butuh penanganan intensif')
                ->descriptionIcon('heroicon-m-heart', IconPosition::Before)
                ->icon('heroicon-o-heart')
                ->color('warning'),

            Stat::make('Luka Ringan', number_format($stats['luka_ringan']))
                ->description('Penanganan medis dasar')
                ->descriptionIcon('heroicon-m-plus-circle', IconPosition::Before)
                ->icon('heroicon-o-plus-circle')
                ->color('info'),
        ];
    }
}
