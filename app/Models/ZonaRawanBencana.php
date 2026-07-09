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
}
