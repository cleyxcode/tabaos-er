<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faskes extends Model
{
    protected $table = 'faskes';
protected $fillable = [
        'wilayah_id',
        'admin_id',
        'nama',
        'tipe',
        'alamat',
        'latitude',
        'longitude',
        'nomor_telepon',
        'jam_operasional',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'float',
            'longitude' => 'float',
        ];
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function ambulans()
    {
        return $this->hasMany(Ambulans::class);
    }
}
