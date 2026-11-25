<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Pesan sekarang boleh berisi gambar saja tanpa teks.
            $table->text('body')->nullable()->change();

            $table->string('image_path')->nullable()->after('body');
            $table->string('image_mime', 100)->nullable()->after('image_path');
            $table->unsignedInteger('image_size')->nullable()->after('image_mime');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_mime', 'image_size']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->text('body')->nullable(false)->change();
        });
    }
};
