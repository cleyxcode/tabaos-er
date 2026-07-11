<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\DTO\PetaRealtimeFilterDTO;
use App\Models\Wilayah;
use App\Services\PetaRealtimeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use UnitEnum;

final class PetaRealtimePage extends Page
{
    private const PUSAT_AMBON_LAT = -3.6954;

    private const PUSAT_AMBON_LNG = 128.1814;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | UnitEnum | null $navigationGroup = 'Penanganan Bencana';

    protected static ?string $navigationLabel = 'Peta Realtime';

    protected static ?string $title = 'Peta Monitoring Realtime';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'peta-realtime';

    public ?int $wilayahId = null;

    public ?string $jenisKejadian = null;

    public ?string $statusLaporan = null;

    public ?string $statusPenanganan = null;

    public bool $tampilkanLaporan = true;

    public bool $tampilkanRelawan = true;

    public bool $tampilkanFaskes = true;

    public bool $tampilkanEvakuasi = true;

    public bool $tampilkanPetugas = true;

    /** Jarak area tampilan dalam km — kosong = tampilkan semua */
    public ?string $jarakArea = null;

    public ?string $centerLat = null;

    public ?string $centerLng = null;

    public int $relawanStaleMinutes = 30;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filter Data')
                ->description('Saring laporan dan titik yang ditampilkan di peta. Data diperbarui otomatis setiap 5 detik.')
                ->icon('heroicon-o-funnel')
                ->headerActions([
                    Action::make('resetFilters')
                        ->label('Reset Filter')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action('resetFilters'),
                ])
                ->schema([
                    Forms\Components\Select::make('wilayahId')
                        ->label('Wilayah')
                        ->placeholder('Semua wilayah')
                        ->options(fn (): array => Wilayah::query()->orderBy('nama')->pluck('nama', 'id')->all())
                        ->searchable()
                        ->live(),

                    Forms\Components\Select::make('jenisKejadian')
                        ->label('Jenis Kejadian')
                        ->placeholder('Semua jenis')
                        ->options([
                            'Gempa Bumi' => 'Gempa Bumi',
                            'Tsunami' => 'Tsunami',
                            'Tanah Longsor' => 'Tanah Longsor',
                            'Kebakaran' => 'Kebakaran',
                            'Banjir' => 'Banjir',
                            'Lainnya' => 'Lainnya',
                        ])
                        ->live(),

                    Forms\Components\Select::make('statusLaporan')
                        ->label('Status Laporan')
                        ->placeholder('Semua status')
                        ->options([
                            'pending' => 'Pending',
                            'diverifikasi' => 'Diverifikasi',
                            'ditangani' => 'Ditangani',
                            'selesai' => 'Selesai',
                        ])
                        ->live(),

                    Forms\Components\Select::make('statusPenanganan')
                        ->label('Status Penanganan')
                        ->placeholder('Semua penanganan')
                        ->options([
                            'belum_ditangani' => 'Belum Ditangani',
                            'sedang_ditangani' => 'Sedang Ditangani',
                            'selesai_ditangani' => 'Selesai Ditangani',
                        ])
                        ->live(),

                    Forms\Components\Select::make('relawanStaleMinutes')
                        ->label('Relawan dianggap aktif')
                        ->options([
                            15 => 'Update lokasi dalam 15 menit terakhir',
                            30 => 'Update lokasi dalam 30 menit terakhir',
                            60 => 'Update lokasi dalam 1 jam terakhir',
                            120 => 'Update lokasi dalam 2 jam terakhir',
                        ])
                        ->default(30)
                        ->live(),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3]),

            Section::make('Area Tampilan')
                ->description('Opsional. Batasi data hanya di sekitar satu titik — tanpa perlu mengisi koordinat manual.')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\Select::make('jarakArea')
                        ->label('Seberapa luas area yang ditampilkan?')
                        ->placeholder('Tampilkan seluruh peta (tidak dibatasi)')
                        ->options([
                            '5' => 'Sekitar 5 km dari titik pusat',
                            '10' => 'Sekitar 10 km dari titik pusat',
                            '20' => 'Sekitar 20 km dari titik pusat',
                            '50' => 'Sekitar 50 km dari titik pusat',
                        ])
                        ->live()
                        ->afterStateUpdated(function (?string $state): void {
                            if (filled($state) && blank($this->centerLat)) {
                                $this->setPusatAmbon();
                            }
                        })
                        ->helperText('Pilih jarak, lalu tentukan titik pusat dengan tombol di bawah atau dari peta.')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('pusat_area_info')
                        ->label('Titik pusat saat ini')
                        ->content(fn (): string => $this->getPusatAreaDeskripsi())
                        ->visible(fn (Get $get): bool => filled($get('jarakArea')))
                        ->columnSpanFull(),

                    SchemaActions::make([
                        Action::make('pusatAmbon')
                            ->label('Gunakan pusat Kota Ambon')
                            ->icon('heroicon-o-building-office-2')
                            ->color('primary')
                            ->action('setPusatAmbon'),
                    ])
                        ->visible(fn (Get $get): bool => filled($get('jarakArea')))
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make('Tampilkan di Peta')
                ->icon('heroicon-o-eye')
                ->schema([
                    Forms\Components\Toggle::make('tampilkanLaporan')
                        ->label('Laporan Kejadian')
                        ->live(),

                    Forms\Components\Toggle::make('tampilkanRelawan')
                        ->label('Relawan (posisi realtime)')
                        ->live(),

                    Forms\Components\Toggle::make('tampilkanFaskes')
                        ->label('Faskes')
                        ->live(),

                    Forms\Components\Toggle::make('tampilkanEvakuasi')
                        ->label('Titik Evakuasi')
                        ->live(),

                    Forms\Components\Toggle::make('tampilkanPetugas')
                        ->label('Petugas Emergency')
                        ->live(),
                ])
                ->columns(['default' => 2, 'md' => 3, 'xl' => 5]),

            SchemaView::make('filament.pages.peta-realtime')
                ->viewData(fn (): array => [
                    'mapData' => $this->getMapData(),
                    'radiusFilter' => $this->getRadiusFilterForMap(),
                    'areaAktif' => filled($this->jarakArea),
                ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMapData(): array
    {
        return app(PetaRealtimeService::class)->getData($this->buildFilter());
    }

    /**
     * Dipanggil dari Alpine setInterval — hanya mengembalikan JSON peta
     * tanpa me-render ulang seluruh halaman Filament.
     *
     * @return array<string, mixed>
     */
    public function refreshMapData(): array
    {
        return $this->getMapData();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'wilayahId',
            'jenisKejadian',
            'statusLaporan',
            'statusPenanganan',
            'jarakArea',
            'centerLat',
            'centerLng',
        ]);

        $this->tampilkanLaporan = true;
        $this->tampilkanRelawan = true;
        $this->tampilkanFaskes = true;
        $this->tampilkanEvakuasi = true;
        $this->tampilkanPetugas = true;
        $this->relawanStaleMinutes = 30;
    }

    public function setPusatAmbon(): void
    {
        $this->setPusatDariPeta(self::PUSAT_AMBON_LAT, self::PUSAT_AMBON_LNG);
    }

    public function setPusatDariPeta(float $lat, float $lng): void
    {
        $this->centerLat = (string) round($lat, 6);
        $this->centerLng = (string) round($lng, 6);
    }

    public function getPusatAreaDeskripsi(): string
    {
        if (blank($this->jarakArea)) {
            return 'Tidak ada batas area — seluruh data ditampilkan.';
        }

        if (blank($this->centerLat) || blank($this->centerLng)) {
            return 'Belum ditentukan. Klik "Gunakan pusat Kota Ambon" atau tombol di peta.';
        }

        $lat = round((float) $this->centerLat, 4);
        $lng = round((float) $this->centerLng, 4);

        if (abs($lat - self::PUSAT_AMBON_LAT) < 0.0001 && abs($lng - self::PUSAT_AMBON_LNG) < 0.0001) {
            return 'Kota Ambon (pusat default)';
        }

        return "Koordinat: {$lat}, {$lng}";
    }

    /**
     * @return array{lat: ?string, lng: ?string, km: ?string}
     */
    private function getRadiusFilterForMap(): array
    {
        if (blank($this->jarakArea)) {
            return ['lat' => null, 'lng' => null, 'km' => null];
        }

        $lat = $this->centerLat ?? (string) self::PUSAT_AMBON_LAT;
        $lng = $this->centerLng ?? (string) self::PUSAT_AMBON_LNG;

        return [
            'lat' => $lat,
            'lng' => $lng,
            'km' => $this->jarakArea,
        ];
    }

    private function buildFilter(): PetaRealtimeFilterDTO
    {
        $radiusKm = filled($this->jarakArea) ? (float) $this->jarakArea : null;
        $centerLat = filled($this->jarakArea)
            ? (float) ($this->centerLat ?? self::PUSAT_AMBON_LAT)
            : null;
        $centerLng = filled($this->jarakArea)
            ? (float) ($this->centerLng ?? self::PUSAT_AMBON_LNG)
            : null;

        return PetaRealtimeFilterDTO::fromArray([
            'wilayahId' => $this->wilayahId,
            'jenisKejadian' => $this->jenisKejadian,
            'statusLaporan' => $this->statusLaporan,
            'statusPenanganan' => $this->statusPenanganan,
            'tampilkanLaporan' => $this->tampilkanLaporan,
            'tampilkanRelawan' => $this->tampilkanRelawan,
            'tampilkanFaskes' => $this->tampilkanFaskes,
            'tampilkanEvakuasi' => $this->tampilkanEvakuasi,
            'tampilkanPetugas' => $this->tampilkanPetugas,
            'centerLat' => $centerLat,
            'centerLng' => $centerLng,
            'radiusKm' => $radiusKm,
            'relawanStaleMinutes' => $this->relawanStaleMinutes,
        ]);
    }
}
