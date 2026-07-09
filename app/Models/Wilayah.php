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
    ];

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
}
