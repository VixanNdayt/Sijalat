<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi penyimpanan alat (lab/rak) untuk ditampilkan pada kartu katalog.
     */
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->string('lokasi', 100)->nullable()->after('code');
        });

        // Isi lokasi awal berdasarkan jenis alat agar katalog lama tetap informatif.
        $defaults = [
            'alat_jaringan' => 'Lab Jaringan',
            'switch' => 'Lab Jaringan',
            'router' => 'Lab Jaringan',
            'laptop' => 'Lab Komputer',
            'bagian_pc' => 'Lab Komputer',
            'IoT' => 'Lab IoT',
        ];

        foreach ($defaults as $jenis => $lokasi) {
            DB::table('alats')
                ->where('jenis', $jenis)
                ->whereNull('lokasi')
                ->update(['lokasi' => $lokasi]);
        }
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('lokasi');
        });
    }
};
