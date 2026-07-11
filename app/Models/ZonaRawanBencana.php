<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaRawanBencana extends Model
{
    protected $table = 'zona_rawan_bencana';

    protected $fillable = [
        'wilayah_id',
        'created_by',
        'nama_zona',
        'tingkat_risiko',
        'polygon',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
        ];
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function titikEvakuasi()
    {
        return $this->hasMany(TitikEvakuasi::class, 'zona_id');
    }

    public function memilikiPolygon(): bool
    {
        return count($this->polygonCoordsNormalized()) >= 3;
    }

    /** @return list<array{lat: float, lng: float}> */
    public function polygonCoordsNormalized(): array
    {
        return self::normalizePolygonPoints($this->polygon);
    }

    public function polygonTitikCount(): int
    {
        return count($this->polygonCoordsNormalized());
    }

    public function polygonRisikoColor(): string
    {
        return match ($this->tingkat_risiko) {
            'tinggi' => '#ef4444',
            'sedang' => '#f59e0b',
            'rendah' => '#22c55e',
            default  => '#3b82f6',
        };
    }

    /** @return array{lat: float, lng: float, geojson?: array<string, mixed>} */
    public static function toMapPickerState(mixed $state): array
    {
        $points = self::normalizePolygonPoints($state);
        $default = ['lat' => -3.6954, 'lng' => 128.1814];

        if (is_array($state) && isset($state['geojson'])) {
            return array_merge($default, $state);
        }

        if (count($points) < 3) {
            return $default;
        }

        $coordinates = array_map(
            fn (array $point): array => [$point['lng'], $point['lat']],
            $points,
        );

        $first = $coordinates[0];
        $last = $coordinates[array_key_last($coordinates)];
        if ($first !== $last) {
            $coordinates[] = $first;
        }

        $count = count($points);
        $latSum = array_sum(array_column($points, 'lat'));
        $lngSum = array_sum(array_column($points, 'lng'));

        return [
            'lat' => $latSum / $count,
            'lng' => $lngSum / $count,
            'geojson' => [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'properties' => [],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$coordinates],
                    ],
                ]],
            ],
        ];
    }

    /** @return list<array{lat: float, lng: float}> */
    public static function extractPolygonFromMapState(mixed $state): array
    {
        if (is_string($state) && $state !== '') {
            $decoded = json_decode($state, true);
            $state = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($state)) {
            return [];
        }

        if (isset($state[0]) && is_array($state[0]) && (isset($state[0]['lat']) || isset($state[0][0]))) {
            return self::normalizePolygonPoints($state);
        }

        $geojson = $state['geojson'] ?? null;
        if (! is_array($geojson) || empty($geojson['features'])) {
            return [];
        }

        foreach ($geojson['features'] as $feature) {
            $geometry = $feature['geometry'] ?? null;
            if (! is_array($geometry)) {
                continue;
            }

            if (($geometry['type'] ?? '') === 'Polygon') {
                $ring = $geometry['coordinates'][0] ?? [];

                return self::normalizePolygonPoints(array_map(
                    fn (array $coord): array => ['lat' => $coord[1], 'lng' => $coord[0]],
                    $ring,
                ));
            }
        }

        return [];
    }

    /** @return list<array{lat: float, lng: float}> */
    public static function normalizePolygonPoints(mixed $polygon): array
    {
        if (is_string($polygon) && $polygon !== '') {
            $polygon = json_decode($polygon, true);
        }

        if (! is_array($polygon)) {
            return [];
        }

        $points = [];
        foreach ($polygon as $point) {
            if (! is_array($point)) {
                continue;
            }

            $lat = $point['lat'] ?? $point[0] ?? null;
            $lng = $point['lng'] ?? $point[1] ?? null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $points[] = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        if (count($points) > 1) {
            $last = $points[array_key_last($points)];
            $first = $points[0];
            if ($last['lat'] === $first['lat'] && $last['lng'] === $first['lng']) {
                array_pop($points);
            }
        }

        return array_values($points);
    }

    /** @return array<string, mixed> */
    public function toMapPickerViewConfig(): array
    {
        $state = self::toMapPickerState($this->polygon);
        $color = $this->polygonRisikoColor();

        return [
            'type' => 'entry',
            'statePath' => 'zona_view_'.$this->id,
            'draggable' => true,
            'showMarker' => false,
            'tilesUrl' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => null,
            'zoomOffset' => -1,
            'tileSize' => 512,
            'detectRetina' => true,
            'rangeSelectField' => 'distance',
            'minZoom' => 0,
            'maxZoom' => 28,
            'zoom' => 12,
            'clickable' => false,
            'markerColor' => $color,
            'liveLocation' => false,
            'bounds' => false,
            'showMyLocationButton' => [false, false, 5000],
            'default' => ['lat' => $state['lat'], 'lng' => $state['lng']],
            'markerHtml' => '',
            'markerIconUrl' => null,
            'markerIconSize' => [36, 36],
            'markerIconClassName' => '',
            'markerIconAnchor' => [18, 36],
            'geojson' => $state['geojson'] ?? null,
            'geoMan' => [
                'show' => true,
                'editable' => false,
                'position' => 'topleft',
                'drawCircleMarker' => false,
                'rotateMode' => false,
                'drawMarker' => false,
                'drawPolygon' => false,
                'drawPolyline' => false,
                'drawCircle' => false,
                'dragMode' => false,
                'cutPolygon' => false,
                'editPolygon' => false,
                'deleteLayer' => false,
                'color' => $color,
                'filledColor' => $color,
                'snappable' => false,
                'snapDistance' => 20,
                'drawText' => false,
                'drawRectangle' => false,
            ],
            'controls' => [
                'zoomControl' => true,
                'scrollWheelZoom' => 'center',
                'doubleClickZoom' => 'center',
                'touchZoom' => 'center',
                'minZoom' => 1,
                'maxZoom' => 28,
                'zoom' => 12,
                'fullscreenControl' => true,
            ],
        ];
    }
}
