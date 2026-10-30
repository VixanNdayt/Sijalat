<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'alat_id', 'dipinjam_saat', 'dikembalikan_saat', 'jumlah_minta', 'deskripsi_minta', 'status'])]
class Pinjam extends Model
{
    //
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class);
    }

    protected $table = 'pinjams';

    protected $fillable = [
        'user_id',
        'alat_id',
        'dipinjam_saat',
        'dikembalikan_saat',
        'jumlah_minta',
        'deskripsi_minta',
        'status',
    ];
}
