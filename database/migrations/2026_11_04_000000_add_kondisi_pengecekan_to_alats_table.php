<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->integer('kondisi_pengecekan')->default(0)->after('kondisi_maintenance');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('kondisi_pengecekan');
        });
    }
};
