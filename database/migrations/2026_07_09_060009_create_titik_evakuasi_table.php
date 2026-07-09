<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titik_evakuasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable()->constrained('zona_rawan_bencana')->nullOnDelete();
            $table->string('nama');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('kapasitas')->nullable();
            $table->text('fasilitas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titik_evakuasi');
    }
};
