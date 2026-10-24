<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'nama', 'jumlah_stok'])]

class Alat extends Model
{
    // Sesuaikan jika nama tabel di MySQL kamu 'equipment' atau 'alat'
    // Secara default, Laravel akan mencari tabel bernama 'alats'
    protected $table = 'alats';

    // Properti wajib untuk izin input data via mass assignment
    protected $fillable = [
        'code',
        'nama',
        'jumlah_stok',
        'img',
    ];
}
