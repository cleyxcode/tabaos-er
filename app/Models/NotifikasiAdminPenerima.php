<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotifikasiAdminPenerima extends Model
{
    protected $table = 'notifikasi_admin_penerima';

    protected $fillable = [
        'notifikasi_admin_id',
        'penerima_type',
        'penerima_id',
        'sudah_dibaca',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
            'dibaca_at' => 'datetime',
        ];
    }

    public function notifikasi(): BelongsTo
    {
        return $this->belongsTo(NotifikasiAdmin::class, 'notifikasi_admin_id');
    }

    public function penerima(): MorphTo
    {
        return $this->morphTo();
    }
}
