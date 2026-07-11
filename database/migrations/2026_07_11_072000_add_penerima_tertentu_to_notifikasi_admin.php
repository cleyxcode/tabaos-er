<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_admin', function (Blueprint $table) {
            $table->boolean('kirim_semua_relawan')->default(true)->after('kirim_ke_faskes');
            $table->boolean('kirim_semua_faskes')->default(true)->after('kirim_semua_relawan');
            $table->json('akun_relawan_ids')->nullable()->after('kirim_semua_faskes');
            $table->json('akun_faskes_ids')->nullable()->after('akun_relawan_ids');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_admin', function (Blueprint $table) {
            $table->dropColumn([
                'kirim_semua_relawan',
                'kirim_semua_faskes',
                'akun_relawan_ids',
                'akun_faskes_ids',
            ]);
        });
    }
};
