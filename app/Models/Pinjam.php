<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['kode_pinjam', 'alat_id', 'guru_id', 'mapel_id', 'dipinjam_saat', 'ruangan', 'dikembalikan_saat', 'jumlah_minta', 'deskripsi_minta', 'bukti_ambil', 'bukti_kembali', 'catatan', 'kondisi_kembali', 'catatan_kembali'])]
class Pinjam extends Model
{
    protected $table = 'pinjams';

    /**
     * Kolom kepemilikan, token QR, status persetujuan, dan waktu pengembalian
     * aktual sengaja tidak fillable agar tidak bisa diubah lewat mass assignment
     * dari input pengguna. Nilainya hanya diisi eksplisit oleh controller yang
     * sudah memverifikasi hak akses.
     */
    protected $fillable = [
        'kode_pinjam',
        'alat_id',
        'guru_id',
        'mapel_id',
        'dipinjam_saat',
        'dikembalikan_saat',
        'jumlah_minta',
        'deskripsi_minta',
        'bukti_ambil',
        'ruangan',
        'bukti_kembali',
        'catatan',
        'kondisi_kembali',
        'catatan_kembali',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

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

        return (int) $tenggat->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay());
    }

    /**
     * Label keterlambatan yang mudah dibaca. Bisa berupa menit/jam untuk
     * keterlambatan pada hari yang sama, atau hari untuk lewat tenggat.
     */
    public function getTerlambatLabelAttribute(): ?string
    {
        $tenggat = $this->dikembalikan_saat ? \Carbon\Carbon::parse($this->dikembalikan_saat) : null;

        if (! $tenggat || $this->status === 'ditolak') {
            return null;
        }

        $selesai = $this->dikembalikan_aktual
            ? \Carbon\Carbon::parse($this->dikembalikan_aktual)
            : ($this->status === 'dipinjam' ? now() : null);

        if (! $selesai || $selesai->lessThanOrEqualTo($tenggat)) {
            return null;
        }

        $menit = (int) $tenggat->diffInMinutes($selesai);
        $bedaHari = (int) $tenggat->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay());
        $jam = intdiv($menit, 60);

        if ($bedaHari >= 1) {
            $sisaJam = $jam % 24;

            return 'Terlambat ' . $bedaHari . ' hari'
                . ($sisaJam > 0 ? ' ' . $sisaJam . ' jam' : '');
        }

        if ($jam < 1) {
            return 'Terlambat ' . max(1, $menit) . ' menit';
        }

        return 'Terlambat ' . $jam . ' jam';
    }

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
     * Label status ringkas untuk badge pada tabel dashboard.
     */
    public function getStatusShortLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'disetuji' => 'Disetujui',
            'dipinjam' => 'Dipinjam',
            'dikembalikan' => 'Dikembalikan',
            'ditolak' => 'Ditolak',
            'terlambat' => 'Terlambat',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Nama kelas warna badge status, dipakai bersama CSS .status-badge.
     */
    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'pending',
            'disetuji' => 'disetujui',
            'dipinjam' => 'dipinjam',
            'dikembalikan' => 'dikembalikan',
            'ditolak', 'terlambat' => 'ditolak',
            default => 'netral',
        };
    }

    public function getKondisiKembaliLabelAttribute(): ?string
    {
        return match ($this->kondisi_kembali) {
            'baik' => 'Baik',
            'pengecekan' => 'Butuh Pengecekan',
            'rusak' => 'Rusak',
            default => null,
        };
    }

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

    public function kodeQr(string $jenis): string
    {
        $jenis = strtolower($jenis);
        $token = $jenis === 'kembali' ? $this->qr_kembali_token : $this->qr_ambil_token;

        return 'SIJALAT-' . strtoupper($jenis) . '-' . $this->id . '-' . $token;
    }

    public static function generateKodePinjam(): string
    {
        do {
            $kode = 'SJL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        } while (self::where('kode_pinjam', $kode)->exists());

        return $kode;
    }
}