<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelawanNotifikasi extends Model
{
    protected $table = 'relawan_notifikasi';

    protected $fillable = [
        'akun_relawan_id',
        'laporan_id',
        'sudah_dibaca',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
            'dibaca_at'    => 'datetime',
        ];
    }

    public function akunRelawan()
    {
        return $this->belongsTo(AkunRelawan::class);
    }

    public function laporan()
    {
        return $this->belongsTo(LaporanBencana::class, 'laporan_id');
    }
}
