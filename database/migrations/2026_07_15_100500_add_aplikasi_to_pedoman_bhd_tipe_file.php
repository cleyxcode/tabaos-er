<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite menyimpan enum sebagai string; nilai 'aplikasi' sudah diterima.
            return;
        }

        DB::statement("ALTER TABLE pedoman_bhd MODIFY tipe_file ENUM('pdf', 'video', 'gambar', 'dokumen', 'aplikasi') NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::table('pedoman_bhd')
            ->where('tipe_file', 'aplikasi')
            ->update(['tipe_file' => 'dokumen']);

        DB::statement("ALTER TABLE pedoman_bhd MODIFY tipe_file ENUM('pdf', 'video', 'gambar', 'dokumen') NOT NULL");
    }
};
