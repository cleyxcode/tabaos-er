<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->foreignId('akun_relawan_ditugaskan')
                  ->nullable()
                  ->nullOnDelete()
                  ->constrained('akun_relawan')
                  ->after('status');
            $table->enum('status_penanganan', [
                'belum_ditangani',
                'sedang_ditangani',
                'selesai_ditangani',
            ])->default('belum_ditangani')->after('akun_relawan_ditugaskan');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->dropForeign(['akun_relawan_ditugaskan']);
            $table->dropColumn(['akun_relawan_ditugaskan', 'status_penanganan']);
        });
    }
};
