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
        $polygon = $this->polygon;
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

        return $points;
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
}
