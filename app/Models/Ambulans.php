<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ambulans extends Model
{
    protected $table = 'ambulans';

    protected $fillable = [
        'faskes_id',
        'nama_layanan',
        'nomor_telepon',
        'status',
        'jenis_layanan',
    ];

    public function faskes()
    {
        return $this->belongsTo(Faskes::class);
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class);
    }
}
