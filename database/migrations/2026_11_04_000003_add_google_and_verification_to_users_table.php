<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->timestamp('last_login_at')->nullable()->after('password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        // Akun lama tetap bisa masuk tanpa harus verifikasi ulang.
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
