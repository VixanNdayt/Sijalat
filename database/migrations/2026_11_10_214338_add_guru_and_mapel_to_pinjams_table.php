<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->foreignId('guru_id')->nullable()->after('alat_id')->constrained('gurus')->nullOnDelete();
            $table->foreignId('mapel_id')->nullable()->after('guru_id')->constrained('mapels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->dropForeign(['mapel_id']);
            $table->dropColumn(['guru_id', 'mapel_id']);
        });
    }
};