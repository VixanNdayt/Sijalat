<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'alat_id', 'dipinjam_saat','dikembalikan_saat','jumlah_minta','deskripsi_minta', 'status'])]
class pinjam extends Model
{
    //
    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function alat()
    {
        return $this->belongsTo(alat::class);
    }

    protected $table = 'pinjams';

    protected $fillable = [
        'user_id',
        'alat_id',
        'dipinjam_saat',
        'dikembailkan_saat',
        'jumlah_minta',
        'deskripsi_minta',
        'status',
    ];
}
