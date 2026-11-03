<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['kode_pinjam', 'qr_ambil_token', 'qr_kembali_token', 'user_id', 'alat_id', 'dipinjam_saat', 'ruangan','dikembalikan_saat', 'jumlah_minta', 'deskripsi_minta', 'bukti_ambil', 'bukti_kembali', 'catatan', 'status'])]
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
        'qr_ambil_token',
        'qr_kembali_token',
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
     * Generate token acak untuk QR pengambilan / pengembalian.
     */
    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(48);
        } while (
            self::where('qr_ambil_token', $token)->exists() ||
            self::where('qr_kembali_token', $token)->exists()
        );

        return $token;
    }

    /**
     * Isi token QR jika belum ada (untuk data lama).
     */
    public function pastikanTokenQr(): void
    {
        $berubah = false;

        if (empty($this->qr_ambil_token)) {
            $this->qr_ambil_token = self::generateQrToken();
            $berubah = true;
        }

        if (empty($this->qr_kembali_token)) {
            $this->qr_kembali_token = self::generateQrToken();
            $berubah = true;
        }

        if ($berubah) {
            $this->save();
        }
    }

    /**
     * Kode yang tertanam di dalam QR untuk satu jenis transaksi.
     *
     * @param string $jenis "ambil" atau "kembali"
     */
    public function kodeQr(string $jenis): string
    {
        $jenis = strtolower($jenis);
        $token = $jenis === 'kembali' ? $this->qr_kembali_token : $this->qr_ambil_token;

        return 'SIJALAT-' . strtoupper($jenis) . '-' . $this->id . '-' . $token;
    }

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
