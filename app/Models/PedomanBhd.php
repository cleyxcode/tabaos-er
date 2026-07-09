<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedomanBhd extends Model
{
    protected $table = 'pedoman_bhd';
protected $fillable = [
        'judul',
        'tipe_file',
        'deskripsi',
        'file_path',
        'uploaded_by',
    ];

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
