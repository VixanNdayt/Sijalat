<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Siswa pemilik percakapan.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pengguna yang mengirim pesan (siswa atau admin).
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Jumlah pesan yang belum dibaca untuk pengguna ini.
     */
    public static function unreadCountFor(User $user): int
    {
        if ($user->isAdmin()) {
            return static::query()
                ->whereColumn('sender_id', 'user_id')
                ->whereNull('read_at')
                ->count();
        }

        return static::query()
            ->where('user_id', $user->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
