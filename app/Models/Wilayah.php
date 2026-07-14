<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $table = 'wilayah';

    protected $fillable = [
        'nama',
        'kecamatan',
        'kota',
        'pulau',
        'provinsi',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function laporan()
    {
        return $this->hasMany(LaporanBencana::class);
    }

    public function faskes()
    {
        return $this->hasMany(Faskes::class);
    }

    public function zonaRawan()
    {
        return $this->hasMany(ZonaRawanBencana::class);
    }

    public function getLabelLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->nama,
            $this->pulau,
            $this->kota,
            $this->provinsi,
        ]);

        return implode(' — ', $parts);
    }

    /**
     * @return array<string, string>
     */
    public static function provinsiList(): array
    {
        return static::query()
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi', 'provinsi')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function pulauList(?string $provinsi = null): array
    {
        return static::query()
            ->when($provinsi, fn ($q) => $q->where('provinsi', $provinsi))
            ->whereNotNull('pulau')
            ->where('pulau', '!=', '')
            ->distinct()
            ->orderBy('pulau')
            ->pluck('pulau', 'pulau')
            ->all();
    }
}
