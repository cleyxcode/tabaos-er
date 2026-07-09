<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_bencana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayah')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_pelapor');
            $table->string('nomor_kontak');
            $table->string('jenis_kejadian');
            $table->boolean('di_lokasi_kejadian')->default(true);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('alamat_lokasi')->nullable();
            $table->dateTime('tanggal_kejadian');
            $table->text('deskripsi');
            $table->json('foto')->nullable();
            $table->integer('meninggal_jumlah')->default(0);
            $table->string('meninggal_jenis_kelamin')->nullable();
            $table->text('penyebab_meninggal')->nullable();
            $table->integer('hilang_jumlah')->default(0);
            $table->string('hilang_jenis_kelamin')->nullable();
            $table->integer('luka_berat_jumlah')->default(0);
            $table->string('luka_berat_jenis_kelamin')->nullable();
            $table->text('penyebab_luka_berat')->nullable();
            $table->integer('luka_ringan_jumlah')->default(0);
            $table->string('luka_ringan_jenis_kelamin')->nullable();
            $table->text('penyebab_luka_ringan')->nullable();
            $table->enum('status', ['pending', 'diverifikasi', 'ditangani', 'selesai'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_bencana');
    }
};
