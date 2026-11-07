<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->string('kondisi_kembali', 20)->nullable()->after('catatan');
            $table->text('catatan_kembali')->nullable()->after('kondisi_kembali');
            $table->timestamp('dikembalikan_aktual')->nullable()->after('dikembalikan_saat');
        });
    }

    public function down(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->dropColumn(['kondisi_kembali', 'catatan_kembali', 'dikembalikan_aktual']);
        });
    }
};
