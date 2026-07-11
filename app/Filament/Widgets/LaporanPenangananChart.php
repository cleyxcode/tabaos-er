<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\DashboardStatistikService;
use Filament\Widgets\ChartWidget;

final class LaporanPenangananChart extends ChartWidget
{
    protected static ?int $sort = 7;

    protected ?string $heading = 'Status Penanganan';

    protected ?string $description = 'Progres penanganan laporan oleh relawan';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $chart = app(DashboardStatistikService::class)->laporanPerPenanganan();

        return [
            'datasets' => [
                [
                    'label' => 'Laporan',
                    'data' => $chart['data'],
                    'backgroundColor' => [
                        '#ef4444',
                        '#f59e0b',
                        '#16a34a',
                    ],
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
