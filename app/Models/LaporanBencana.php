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

    public function memilikiKoordinat(): bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return false;
        }

        return abs((float) $this->latitude) > 0.000001 || abs((float) $this->longitude) > 0.000001;
    }

    public function koordinatLabel(): ?string
    {
        if (! $this->memilikiKoordinat()) {
            return null;
        }

        return sprintf('%.5f, %.5f', $this->latitude, $this->longitude);
    }

    public function googleMapsUrl(): ?string
    {
        if (! $this->memilikiKoordinat()) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode("{$lat},{$lng}");
    }
}
