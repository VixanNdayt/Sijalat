<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->string('qr_ambil_token', 64)->nullable()->unique()->after('kode_pinjam');
            $table->string('qr_kembali_token', 64)->nullable()->unique()->after('qr_ambil_token');
        });

        // Isi token untuk data lama agar QR tetap bisa dibuat
        DB::table('pinjams')->orderBy('id')->each(function ($pinjam) {
            DB::table('pinjams')->where('id', $pinjam->id)->update([
                'qr_ambil_token'   => Str::random(48),
                'qr_kembali_token' => Str::random(48),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pinjams', function (Blueprint $table) {
            $table->dropUnique(['qr_ambil_token']);
            $table->dropUnique(['qr_kembali_token']);
            $table->dropColumn(['qr_ambil_token', 'qr_kembali_token']);
        });
    }
};
