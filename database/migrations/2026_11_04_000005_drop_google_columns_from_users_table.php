<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghapus kolom yang hanya dipakai oleh fitur login Google.
     */
    public function up(): void
    {
        $indexes = array_column(Schema::getIndexes('users'), 'name');

        if (Schema::hasColumn('users', 'google_id') && in_array('users_google_id_unique', $indexes, true)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['google_id']);
            });
        }

        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_id');
            });
        }

        if (Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('google_id')->nullable()->unique()->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar')->nullable()->after('google_id');
            });
        }
    }
};
