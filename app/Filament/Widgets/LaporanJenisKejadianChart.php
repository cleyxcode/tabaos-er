<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\DashboardStatistikService;
use Filament\Widgets\ChartWidget;

final class LaporanJenisKejadianChart extends ChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Jenis Kejadian';

    protected ?string $description = 'Distribusi laporan berdasarkan jenis bencana';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $chart = app(DashboardStatistikService::class)->laporanPerJenisKejadian();

        $colors = [
            '#ef4444',
            '#f97316',
            '#eab308',
            '#22c55e',
            '#3b82f6',
            '#8b5cf6',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah laporan',
                    'data' => $chart['data'],
                    'backgroundColor' => array_slice($colors, 0, count($chart['data'])),
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
