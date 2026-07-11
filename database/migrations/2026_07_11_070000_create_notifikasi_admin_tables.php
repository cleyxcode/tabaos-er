<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('pesan');
            $table->string('gambar')->nullable();
            $table->boolean('kirim_ke_relawan')->default(false);
            $table->boolean('kirim_ke_faskes')->default(false);
            $table->enum('status', ['draft', 'terkirim', 'gagal'])->default('draft');
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi_admin_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notifikasi_admin_id')
                ->constrained('notifikasi_admin')
                ->cascadeOnDelete();
            $table->string('penerima_type');
            $table->unsignedBigInteger('penerima_id');
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['notifikasi_admin_id', 'penerima_type', 'penerima_id'],
                'notif_admin_penerima_unique',
            );
            $table->index(['penerima_type', 'penerima_id'], 'notif_admin_penerima_morph_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_admin_penerima');
        Schema::dropIfExists('notifikasi_admin');
    }
};
