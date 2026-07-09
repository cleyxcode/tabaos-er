<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitikEvakuasi extends Model
{
    protected $table = 'titik_evakuasi';
protected $fillable = [
        'zona_id',
        'nama',
        'latitude',
        'longitude',
        'kapasitas',
        'fasilitas',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'kapasitas' => 'integer',
        ];
    }

    public function zona()
    {
        return $this->belongsTo(ZonaRawanBencana::class, 'zona_id');
    }
}
