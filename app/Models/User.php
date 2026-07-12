<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    // Relations
    public function laporanDiverifikasi()
    {
        return $this->hasMany(LaporanBencana::class, 'verified_by');
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class, 'petugas_id');
    }

    public function faskesDikelola()
    {
        return $this->hasMany(Faskes::class, 'admin_id');
    }

    public function petugasEmergency()
    {
        return $this->hasOne(PetugasEmergency::class);
    }

    public function relawanDisetujui()
    {
        return $this->hasMany(Relawan::class, 'approved_by');
    }

    public function zonaDibuat()
    {
        return $this->hasMany(ZonaRawanBencana::class, 'created_by');
    }

    public function pedomanDiunggah()
    {
        return $this->hasMany(PedomanBhd::class, 'uploaded_by');
    }

    public function notifikasiAdmin()
    {
        return $this->hasMany(NotifikasiAdmin::class, 'admin_id');
    }
}
