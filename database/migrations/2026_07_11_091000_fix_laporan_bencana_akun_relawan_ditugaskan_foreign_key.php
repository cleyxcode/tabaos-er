<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->dropForeign(['akun_relawan_ditugaskan']);
        });

        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->foreign('akun_relawan_ditugaskan')
                ->references('id')
                ->on('akun_relawan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->dropForeign(['akun_relawan_ditugaskan']);
        });

        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->foreign('akun_relawan_ditugaskan')
                ->references('id')
                ->on('akun_relawan');
        });
    }
};
