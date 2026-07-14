<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\WilayahAdminSupport;
use Filament\Forms;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Operasional';

    protected static ?string $navigationLabel = 'Dashboard';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filter Wilayah')
                ->description('Tampilkan statistik untuk semua data, atau saring per provinsi, pulau, kota, maupun wilayah. Siap multi-provinsi (Maluku dan seterusnya).')
                ->icon('heroicon-o-globe-asia-australia')
                ->schema([
                    Forms\Components\Select::make('provinsi')
                        ->label('Provinsi')
                        ->placeholder('Semua provinsi')
                        ->options(fn (): array => WilayahAdminSupport::provinsiOptions())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('pulau', null);
                            $set('kota', null);
                            $set('wilayah_id', null);
                        }),

                    Forms\Components\Select::make('pulau')
                        ->label('Pulau')
                        ->placeholder('Semua pulau')
                        ->options(fn (Get $get): array => WilayahAdminSupport::pulauOptions($get('provinsi')))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('kota', null);
                            $set('wilayah_id', null);
                        }),

                    Forms\Components\Select::make('kota')
                        ->label('Kota/Kabupaten')
                        ->placeholder('Semua kota')
                        ->options(fn (Get $get): array => WilayahAdminSupport::kotaOptions(
                            $get('provinsi'),
                            $get('pulau'),
                        ))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('wilayah_id', null)),

                    Forms\Components\Select::make('wilayah_id')
                        ->label('Wilayah')
                        ->placeholder('Semua wilayah')
                        ->options(fn (Get $get): array => WilayahAdminSupport::wilayahOptions(
                            $get('provinsi'),
                            $get('pulau'),
                            $get('kota'),
                        ))
                        ->searchable(),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return int | array<string, int | null>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
