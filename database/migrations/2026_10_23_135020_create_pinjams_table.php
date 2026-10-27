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
        Schema::create('pinjams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('alat_id')->constrained('alats')->onDelete('cascade');
            
            $table->dateTime('dipinjam_saat'); // Waktu peminjaman
            $table->dateTime('dikembalikan_saat')->nullable(); // Waktu pengembalian
            $table->integer('jumlah_minta')->default(1); // Jumlah alat yang dipinjam
            $table->text('deskripsi_minta'); // Deskripsi keperluan peminjaman
            $table->enum('status', ['pending', 'disetuji', 'dipinjam', 'ditolak', 'dikembalikan'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjams');
    }
};
