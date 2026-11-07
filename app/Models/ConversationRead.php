<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penanda pesan terakhir yang sudah dibaca oleh satu pengguna pada
 * satu percakapan siswa. Dibuat per pembaca agar dua admin tidak saling
 * menghapus status "belum dibaca".
 */
class ConversationRead extends Model
{
    protected $fillable = [
        'user_id',
        'reader_id',
        'last_read_message_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reader()
    {
        return $this->belongsTo(User::class, 'reader_id');
    }
}
