<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanBencana extends Model
{
    protected $table = 'laporan_bencana';
protected $fillable = [
        'pengguna_id',
        'wilayah_id',
        'verified_by',
        'nama_pelapor',
        'nomor_kontak',
        'jenis_kejadian',
        'di_lokasi_kejadian',
        'latitude',
        'longitude',
        'alamat_lokasi',
        'tanggal_kejadian',
        'deskripsi',
        'foto',
        'meninggal_jumlah',
        'meninggal_jenis_kelamin',
        'penyebab_meninggal',
        'hilang_jumlah',
        'hilang_jenis_kelamin',
        'luka_berat_jumlah',
        'luka_berat_jenis_kelamin',
        'penyebab_luka_berat',
        'luka_ringan_jumlah',
        'luka_ringan_jenis_kelamin',
        'penyebab_luka_ringan',
        'status',
        'akun_relawan_ditugaskan',
        'status_penanganan',
        'relawan_sampai_notified_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'foto' => 'array',
            'tanggal_kejadian' => 'datetime',
            'verified_at' => 'datetime',
            'relawan_sampai_notified_at' => 'datetime',
            'di_lokasi_kejadian' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'meninggal_jumlah' => 'integer',
            'hilang_jumlah' => 'integer',
            'luka_berat_jumlah' => 'integer',
            'luka_ringan_jumlah' => 'integer',
        ];
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class);
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class, 'laporan_id');
    }

    public function relawanDitugaskan()
    {
        return $this->belongsTo(AkunRelawan::class, 'akun_relawan_ditugaskan');
    }
}
