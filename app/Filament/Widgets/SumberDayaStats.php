<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\AmbulansResource;
use App\Filament\Resources\FaskesResource;
use App\Filament\Resources\PenggunaResource;
use App\Filament\Resources\PetugasEmergencyResource;
use App\Filament\Resources\ZonaRawanBencanaResource;
use App\Services\DashboardStatistikService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SumberDayaStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Sumber Daya & Infrastruktur';

    protected ?string $description = 'Faskes, armada, petugas, dan titik mitigasi bencana';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = app(DashboardStatistikService::class)->sumberDaya();

        return [
            Stat::make('Faskes Terdaftar', $stats['faskes'])
                ->description("RS {$stats['faskes_rs']} · Puskesmas {$stats['faskes_puskesmas']} · Apotek {$stats['faskes_apotek']}")
                ->descriptionIcon('heroicon-m-building-office-2', IconPosition::Before)
                ->icon('heroicon-o-building-office-2')
                ->color('info')
                ->url(FaskesResource::getUrl('index')),

            Stat::make('Ambulans Tersedia', $stats['ambulans_tersedia'])
                ->description("{$stats['ambulans_total']} total armada ambulans")
                ->descriptionIcon('heroicon-m-truck', IconPosition::Before)
                ->icon('heroicon-o-truck')
                ->color('success')
                ->url(AmbulansResource::getUrl('index')),

            Stat::make('Petugas Emergency', $stats['petugas_aktif'])
                ->description("{$stats['petugas_total']} petugas terdaftar")
                ->descriptionIcon('heroicon-m-shield-check', IconPosition::Before)
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->url(PetugasEmergencyResource::getUrl('index')),

            Stat::make('Titik Evakuasi', $stats['titik_evakuasi'])
                ->description("Zona rawan: {$stats['zona_rawan']} area")
                ->descriptionIcon('heroicon-m-map-pin', IconPosition::Before)
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->url(ZonaRawanBencanaResource::getUrl('index')),

            Stat::make('Zona Rawan Bencana', $stats['zona_rawan'])
                ->description("Tinggi {$stats['zona_tinggi']} · Sedang {$stats['zona_sedang']} · Rendah {$stats['zona_rendah']}")
                ->descriptionIcon('heroicon-m-map', IconPosition::Before)
                ->icon('heroicon-o-map')
                ->color('danger')
                ->url(ZonaRawanBencanaResource::getUrl('index')),

            Stat::make('Pengguna Aplikasi', $stats['pengguna'])
                ->description("{$stats['notifikasi_admin']} broadcast admin terkirim")
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->icon('heroicon-o-users')
                ->color('gray')
                ->url(PenggunaResource::getUrl('index')),
        ];
    }
}
