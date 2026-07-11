<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetugasEmergency extends Model
{
    protected $table = 'petugas_emergency';

    protected $fillable = [
        'user_id',
        'nama',
        'kategori',
        'nomor_telepon',
        'latitude',
        'longitude',
        'alamat',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'float',
            'longitude' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
