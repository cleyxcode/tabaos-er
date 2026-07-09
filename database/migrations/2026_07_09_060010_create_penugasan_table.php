<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_bencana')->cascadeOnDelete();
            $table->foreignId('relawan_id')->nullable()->constrained('relawan')->nullOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ambulans_id')->nullable()->constrained('ambulans')->nullOnDelete();
            $table->enum('status', ['ditugaskan', 'dalam_perjalanan', 'selesai', 'dibatalkan'])->default('ditugaskan');
            $table->text('catatan')->nullable();
            $table->timestamp('ditugaskan_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan');
    }
};
