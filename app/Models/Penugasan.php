<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penugasan extends Model
{
    protected $table = 'penugasan';
protected $fillable = [
        'laporan_id',
        'relawan_id',
        'petugas_id',
        'ambulans_id',
        'status',
        'catatan',
        'ditugaskan_at',
        'selesai_at',
    ];

    protected function casts(): array
    {
        return [
            'ditugaskan_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function laporan()
    {
        return $this->belongsTo(LaporanBencana::class);
    }

    public function relawan()
    {
        return $this->belongsTo(Relawan::class);
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function ambulans()
    {
        return $this->belongsTo(Ambulans::class);
    }
}
