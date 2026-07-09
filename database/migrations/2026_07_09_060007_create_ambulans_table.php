<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambulans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->constrained('faskes')->cascadeOnDelete();
            $table->string('nama_layanan');
            $table->string('nomor_telepon');
            $table->enum('status', ['tersedia', 'tidak_tersedia'])->default('tersedia');
            $table->enum('jenis_layanan', ['gratis', 'berbayar']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambulans');
    }
};
