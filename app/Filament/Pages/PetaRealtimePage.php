<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\DTO\PetaRealtimeFilterDTO;
use App\Models\Wilayah;
use App\Services\PetaRealtimeService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use UnitEnum;

final class PetaRealtimePage extends Page
{
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

    public ?string $centerLat = null;

    public ?string $centerLng = null;

    public ?string $radiusKm = null;

    public int $relawanStaleMinutes = 30;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('filament.pages.peta-realtime')
                ->poll('10s')
                ->viewData(fn (): array => [
                    'mapData' => $this->getMapData(),
                    'radiusFilter' => [
                        'lat' => $this->centerLat,
                        'lng' => $this->centerLng,
                        'km' => $this->radiusKm,
                    ],
                    'wilayahOptions' => Wilayah::query()->orderBy('nama')->pluck('nama', 'id')->all(),
                    'jenisKejadianOptions' => [
                        'Gempa Bumi' => 'Gempa Bumi',
                        'Tsunami' => 'Tsunami',
                        'Tanah Longsor' => 'Tanah Longsor',
                        'Kebakaran' => 'Kebakaran',
                        'Banjir' => 'Banjir',
                        'Lainnya' => 'Lainnya',
                    ],
                    'statusLaporanOptions' => [
                        'pending' => 'Pending',
                        'diverifikasi' => 'Diverifikasi',
                        'ditangani' => 'Ditangani',
                        'selesai' => 'Selesai',
                    ],
                    'statusPenangananOptions' => [
                        'belum_ditangani' => 'Belum Ditangani',
                        'sedang_ditangani' => 'Sedang Ditangani',
                        'selesai_ditangani' => 'Selesai Ditangani',
                    ],
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

    public function resetFilters(): void
    {
        $this->reset([
            'wilayahId',
            'jenisKejadian',
            'statusLaporan',
            'statusPenanganan',
            'centerLat',
            'centerLng',
            'radiusKm',
        ]);

        $this->tampilkanLaporan = true;
        $this->tampilkanRelawan = true;
        $this->tampilkanFaskes = true;
        $this->tampilkanEvakuasi = true;
        $this->tampilkanPetugas = true;
        $this->relawanStaleMinutes = 30;
    }

    private function buildFilter(): PetaRealtimeFilterDTO
    {
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
            'centerLat' => $this->centerLat,
            'centerLng' => $this->centerLng,
            'radiusKm' => $this->radiusKm,
            'relawanStaleMinutes' => $this->relawanStaleMinutes,
        ]);
    }
}
