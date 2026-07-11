<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotifikasiAdmin extends Model
{
    protected $table = 'notifikasi_admin';

    protected $fillable = [
        'admin_id',
        'judul',
        'pesan',
        'gambar',
        'kirim_ke_relawan',
        'kirim_ke_faskes',
        'kirim_semua_relawan',
        'kirim_semua_faskes',
        'akun_relawan_ids',
        'akun_faskes_ids',
        'status',
        'jumlah_penerima',
        'dikirim_at',
    ];

    protected function casts(): array
    {
        return [
            'kirim_ke_relawan' => 'boolean',
            'kirim_ke_faskes' => 'boolean',
            'kirim_semua_relawan' => 'boolean',
            'kirim_semua_faskes' => 'boolean',
            'akun_relawan_ids' => 'array',
            'akun_faskes_ids' => 'array',
            'jumlah_penerima' => 'integer',
            'dikirim_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function penerima(): HasMany
    {
        return $this->hasMany(NotifikasiAdminPenerima::class);
    }
}
