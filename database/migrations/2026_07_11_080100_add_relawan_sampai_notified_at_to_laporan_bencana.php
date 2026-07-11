<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->timestamp('relawan_sampai_notified_at')
                ->nullable()
                ->after('status_penanganan');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bencana', function (Blueprint $table) {
            $table->dropColumn('relawan_sampai_notified_at');
        });
    }
};
