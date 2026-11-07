<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reader_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'reader_id']);
        });

        // Pindahkan status baca lama (read_at global) ke penanda per pembaca.
        $now = now();

        DB::table('messages')
            ->select('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->each(function ($studentId) use ($now) {
                $lastRead = DB::table('messages')
                    ->where('user_id', $studentId)
                    ->whereNotNull('read_at')
                    ->max('id');

                if ($lastRead) {
                    DB::table('conversation_reads')->insertOrIgnore([
                        'user_id' => $studentId,
                        'reader_id' => $studentId,
                        'last_read_message_id' => $lastRead,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        // Riwayat lama dianggap sudah dibaca oleh admin yang ada saat migrasi,
        // supaya badge tidak tiba-tiba memunculkan semua percakapan lama.
        $adminIds = DB::table('users')->where('role_user', 'admin')->pluck('id');
        $lastMessageIds = DB::table('messages')
            ->select('user_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('user_id')
            ->pluck('last_id', 'user_id');

        foreach ($adminIds as $adminId) {
            foreach ($lastMessageIds as $studentId => $lastId) {
                DB::table('conversation_reads')->insertOrIgnore([
                    'user_id' => $studentId,
                    'reader_id' => $adminId,
                    'last_read_message_id' => $lastId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_reads');
    }
};
