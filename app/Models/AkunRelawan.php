<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AkunRelawan extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'akun_relawan';

    protected $fillable = [
        'relawan_id',
        'email',
        'password',
        'fcm_token',
        'latitude',
        'longitude',
        'lokasi_updated_at',
        'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'latitude'          => 'float',
            'longitude'         => 'float',
            'lokasi_updated_at' => 'datetime',
        ];
    }

    public function relawan()
    {
        return $this->belongsTo(Relawan::class);
    }

    public function pengguna()
    {
        return $this->hasOneThrough(
            Pengguna::class,
            Relawan::class,
            'id',          // FK di relawan → akun_relawan.relawan_id
            'id',          // FK di pengguna → relawan.pengguna_id
            'relawan_id',  // local key di akun_relawan
            'pengguna_id'  // local key di relawan
        );
    }

    public function notifikasi()
    {
        return $this->hasMany(RelawanNotifikasi::class);
    }

    public function laporanDitangani()
    {
        return $this->hasMany(LaporanBencana::class, 'akun_relawan_ditugaskan');
    }
}
