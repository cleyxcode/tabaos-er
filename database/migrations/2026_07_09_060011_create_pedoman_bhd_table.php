<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedoman_bhd', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->enum('tipe_file', ['pdf', 'video', 'gambar', 'dokumen', 'aplikasi']);
            $table->text('deskripsi');
            $table->string('file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedoman_bhd');
    }
};
