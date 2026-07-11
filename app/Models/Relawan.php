<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relawan extends Model
{
    protected $table = 'relawan';
    protected $fillable = [
        'pengguna_id',
        'umur',
        'alamat',
        'keahlian',
        'organisasi',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'umur' => 'integer',
        ];
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class);
    }

    public function akunRelawan()
    {
        return $this->hasOne(AkunRelawan::class);
    }
}
