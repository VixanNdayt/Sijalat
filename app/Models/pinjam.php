<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['kode_pinjam', 'qr_ambil_token', 'qr_kembali_token', 'user_id', 'alat_id', 'dipinjam_saat', 'ruangan','dikembalikan_saat', 'dikembalikan_aktual', 'jumlah_minta', 'deskripsi_minta', 'bukti_ambil', 'bukti_kembali', 'catatan', 'kondisi_kembali', 'catatan_kembali', 'status'])]
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

    /**
     * Peminjaman yang terlambat: dikembalikan melewati tenggat,
     * masih dipinjam padahal tenggat sudah lewat, atau data lama berstatus 'terlambat'.
     */
    public function scopeTerlambat($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'terlambat')
                ->orWhere(function ($q) {
                    $q->where('status', 'dikembalikan')
                        ->whereNotNull('dikembalikan_aktual')
                        ->whereNotNull('dikembalikan_saat')
                        ->whereColumn('dikembalikan_aktual', '>', 'dikembalikan_saat');
                })
                ->orWhere(function ($q) {
                    $q->where('status', 'dipinjam')
                        ->whereNotNull('dikembalikan_saat')
                        ->where('dikembalikan_saat', '<', now());
                });
        });
    }

    /**
     * Selisih hari keterlambatan (0 jika tidak terlambat).
     */
    public function getHariTerlambatAttribute(): int
    {
        $tenggat = $this->dikembalikan_saat ? \Carbon\Carbon::parse($this->dikembalikan_saat) : null;

        if (! $tenggat || $this->status === 'ditolak') {
            return 0;
        }

        $selesai = $this->dikembalikan_aktual
            ? \Carbon\Carbon::parse($this->dikembalikan_aktual)
            : ($this->status === 'dipinjam' ? now() : null);

        if (! $selesai || $selesai->lessThanOrEqualTo($tenggat)) {
            return 0;
        }

        // Hitung selisih hari kalender agar "terlambat 1 hari" tidak muncul untuk
        // keterlambatan yang sudah lebih dari sehari.
        return (int) $tenggat->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay());
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
        'dikembalikan_aktual',
        'jumlah_minta',
        'deskripsi_minta',
        'bukti_ambil',
        'ruangan',
        'bukti_kembali',
        'catatan',
        'kondisi_kembali',
        'catatan_kembali',
        'status',
    ];

    /**
     * Label status yang ramah dibaca di tampilan.
     * Nilai di database tetap dipertahankan (termasuk 'disetuji' untuk data lama).
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'disetuji' => 'Disetujui',
            'dipinjam' => 'Dipinjam',
            'dikembalikan' => 'Dikembalikan',
            'ditolak' => 'Ditolak',
            'terlambat' => 'Terlambat',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Label kondisi alat saat dikembalikan siswa.
     */
    public function getKondisiKembaliLabelAttribute(): ?string
    {
        return match ($this->kondisi_kembali) {
            'baik' => 'Baik',
            'pengecekan' => 'Butuh Pengecekan',
            'rusak' => 'Rusak',
            default => null,
        };
    }

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
