<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AkunFaskes extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'akun_faskes';

    protected $fillable = [
        'faskes_id',
        'nama_petugas',
        'email',
        'password',
        'fcm_token',
        'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function faskes()
    {
        return $this->belongsTo(Faskes::class);
    }
}
