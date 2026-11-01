<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kode_pinjam', 'user_id', 'alat_id', 'dipinjam_saat', 'ruangan','dikembalikan_saat', 'jumlah_minta', 'deskripsi_minta', 'bukti_ambil', 'bukti_kembali', 'catatan', 'status'])]
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
        'kode_pinjam',
        'user_id',
        'alat_id',
        'dipinjam_saat',
        'dikembalikan_saat',
        'jumlah_minta',
        'deskripsi_minta',
        'bukti_ambil',
        'ruangan',
        'bukti_kembali',
        'catatan',
        'status',
    ];

    /**
     * Generate kode pinjam unik (format: SJL-XXXXXX)
     */
    public static function generateKodePinjam(): string
    {
        do {
            $kode = 'SJL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        } while (self::where('kode_pinjam', $kode)->exists());

        return $kode;
    }
}
