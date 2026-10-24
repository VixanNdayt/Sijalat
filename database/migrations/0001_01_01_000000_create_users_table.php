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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // ID otomatis (Primary Key)
            $table->string('name'); // Nama siswa
            $table->string('nis')->unique(); // NIS unik siswa
            $table->string('email')->unique(); // Email siswa
            $table->string('password');
            $table->enum('role', ['siswa', 'admin'])->default('siswa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
