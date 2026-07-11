<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\DashboardStatistikService;
use Filament\Widgets\ChartWidget;

final class LaporanTrendChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Tren Laporan Masuk';

    protected ?string $description = 'Jumlah laporan bencana baru per hari';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '30s';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari',
            '14' => '14 hari',
            '30' => '30 hari',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $trend = app(DashboardStatistikService::class)->trendLaporan($days);

        return [
            'datasets' => [
                [
                    'label' => 'Laporan masuk',
                    'data' => $trend['data'],
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
