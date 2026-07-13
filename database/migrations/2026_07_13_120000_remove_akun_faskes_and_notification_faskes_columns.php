<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AKUN_FASKES_MORPH = 'App\\Models\\AkunFaskes';

    public function up(): void
    {
        DB::table('notifikasi_admin_penerima')
            ->where('penerima_type', self::AKUN_FASKES_MORPH)
            ->delete();

        Schema::dropIfExists('akun_faskes');

        Schema::table('notifikasi_admin', function (Blueprint $table) {
            $table->dropColumn([
                'kirim_ke_faskes',
                'kirim_semua_faskes',
                'akun_faskes_ids',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_admin', function (Blueprint $table) {
            $table->boolean('kirim_ke_faskes')->default(false)->after('kirim_ke_relawan');
            $table->boolean('kirim_semua_faskes')->default(true)->after('kirim_semua_relawan');
            $table->json('akun_faskes_ids')->nullable()->after('akun_relawan_ids');
        });

        Schema::create('akun_faskes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->constrained('faskes')->cascadeOnDelete();
            $table->string('nama_petugas');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('fcm_token')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }
};
