<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wilayah', function (Blueprint $table) {
            $table->string('pulau')->nullable()->after('kota');
        });
    }

    public function down(): void
    {
        Schema::table('wilayah', function (Blueprint $table) {
            $table->dropColumn('pulau');
        });
    }
};
