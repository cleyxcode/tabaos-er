<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relawan_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_relawan_id')
                  ->constrained('akun_relawan')
                  ->cascadeOnDelete();
            $table->foreignId('laporan_id')
                  ->constrained('laporan_bencana')
                  ->cascadeOnDelete();
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relawan_notifikasi');
    }
};
