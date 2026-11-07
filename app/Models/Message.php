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
     *
     * Status baca disimpan per pembaca (tabel conversation_reads), sehingga
     * admin A membuka percakapan tidak menghapus badge milik admin B.
     */
    public static function unreadCountFor(User $user): int
    {
        $query = static::query()
            ->leftJoin('conversation_reads as cr', function ($join) use ($user) {
                $join->on('cr.user_id', '=', 'messages.user_id')
                    ->where('cr.reader_id', '=', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('cr.last_read_message_id')
                    ->orWhereColumn('messages.id', '>', 'cr.last_read_message_id');
            });

        if ($user->isAdmin()) {
            // Admin hanya menghitung pesan baru dari siswa.
            $query->whereColumn('messages.sender_id', 'messages.user_id');
        } else {
            // Siswa hanya menghitung pesan yang bukan kirimannya sendiri.
            $query->where('messages.user_id', $user->id)
                ->whereColumn('messages.sender_id', '!=', 'messages.user_id');
        }

        return (int) $query->count('messages.id');
    }

    /**
     * Tandai seluruh pesan dalam satu percakapan sudah dibaca oleh pengguna.
     */
    public static function markRead(User $reader, int $conversationUserId): void
    {
        $lastId = (int) static::query()
            ->where('user_id', $conversationUserId)
            ->max('id');

        if ($lastId <= 0) {
            return;
        }

        ConversationRead::updateOrCreate(
            [
                'user_id' => $conversationUserId,
                'reader_id' => $reader->id,
            ],
            [
                'last_read_message_id' => $lastId,
            ]
        );
    }
}
