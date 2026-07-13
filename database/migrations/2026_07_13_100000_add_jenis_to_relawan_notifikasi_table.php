<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->string('jenis', 30)->default('laporan')->after('akun_relawan_id');
            $table->string('judul')->nullable()->after('jenis');
            $table->text('pesan')->nullable()->after('judul');
        });

        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->dropForeign(['laporan_id']);
        });

        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('laporan_id')->nullable()->change();
            $table->foreign('laporan_id')
                ->references('id')
                ->on('laporan_bencana')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->dropForeign(['laporan_id']);
        });

        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('laporan_id')->nullable(false)->change();
            $table->foreign('laporan_id')
                ->references('id')
                ->on('laporan_bencana')
                ->cascadeOnDelete();
        });

        Schema::table('relawan_notifikasi', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'judul', 'pesan']);
        });
    }
};
