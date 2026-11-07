<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel password_reset_tokens bawaan belum punya kolom token,
     * sehingga fitur lupa password tidak bisa menyimpan token.
     */
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('password_reset_tokens', 'token')) {
                $table->string('token')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('password_reset_tokens', 'token')) {
                $table->dropColumn('token');
            }
        });
    }
};
