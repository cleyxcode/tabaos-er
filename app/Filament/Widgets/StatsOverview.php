<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pengguna;
use App\Models\LaporanBencana;
use App\Models\Relawan;
use App\Models\Faskes;
use App\Models\Ambulans;
use App\Models\TitikEvakuasi;
use App\Models\ZonaRawanBencana;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Laporan Bencana (Menunggu)', LaporanBencana::where('status', 'menunggu')->count())
                ->description('Menunggu tindak lanjut')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
                
            Stat::make('Total Laporan', LaporanBencana::count())
                ->description('Semua laporan masuk')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
                
            Stat::make('Fasilitas Kesehatan', Faskes::count())
                ->description('Puskesmas & Rumah Sakit')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
                
            Stat::make('Ambulans Siaga', Ambulans::count())
                ->description('Armada ambulans')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
                
            Stat::make('Titik Evakuasi', TitikEvakuasi::count())
                ->description('Lokasi aman pengungsian')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),
                
            Stat::make('Zona Rawan', ZonaRawanBencana::count())
                ->description('Area rawan bencana')
                ->descriptionIcon('heroicon-m-map')
                ->color('danger'),
                
            Stat::make('Total Relawan', Relawan::count())
                ->description('Relawan terdaftar')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),
                
            Stat::make('Masyarakat', Pengguna::count())
                ->description('Pengguna aplikasi')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
