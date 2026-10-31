<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alats', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode alat (misal: ALT-001)
            $table->string('nama'); // Nama alat
            $table->integer('jumlah_stok'); // Jumlah stok
            $table->string('description');
            $table->enum('jenis', ['alat_jaringan', 'laptop', 'switch', 'bagian_pc', 'router', 'IoT']);
            $table->string('img')->nullable(); // Path/nama file gambar alat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};
