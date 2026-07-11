<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relawan', function (Blueprint $table) {
            $table->unsignedTinyInteger('umur')->nullable()->after('pengguna_id');
        });

        Schema::table('relawan', function (Blueprint $table) {
            $table->dropColumn('nik');
        });
    }

    public function down(): void
    {
        Schema::table('relawan', function (Blueprint $table) {
            $table->string('nik')->nullable()->after('pengguna_id');
        });

        Schema::table('relawan', function (Blueprint $table) {
            $table->dropColumn('umur');
        });
    }
};
