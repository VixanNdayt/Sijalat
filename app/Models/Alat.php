<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'lokasi', 'nama', 'jumlah_stok', 'img', 'description', 'jenis', 'kondisi_baik', 'kondisi_pengecekan', 'kondisi_rusak', 'kondisi_maintenance'])]

class Alat extends Model
{
    /**
     * Label kategori alat. Nilai kunci harus sama dengan enum alats.jenis.
     */
    public const JENIS_LABEL = [
        'alat_jaringan' => 'Perangkat Jaringan',
        'laptop' => 'Laptop',
        'switch' => 'Switch & Hub',
        'bagian_pc' => 'Komponen PC',
        'router' => 'Router & Access Point',
        'IoT' => 'Mikrokontroler & IoT',
    ];

    // Sesuaikan jika nama tabel di MySQL kamu 'equipment' atau 'alat'
    // Secara default, Laravel akan mencari tabel bernama 'alats'
    protected $table = 'alats';

    // Properti wajib untuk izin input data via mass assignment
    protected $fillable = [
        'code',
        'lokasi',
        'nama',
        'jumlah_stok',
        'img',
        'description',
        'jenis',
        'kondisi_baik',
        'kondisi_pengecekan',
        'kondisi_rusak',
        'kondisi_maintenance',
    ];

    public function pinjams()
    {
        return $this->hasMany(Pinjam::class);
    }

    /**
     * Label kategori yang ramah dibaca.
     */
    public static function jenisLabel(?string $jenis): string
    {
        $jenis = (string) $jenis;

        if ($jenis !== '' && isset(self::JENIS_LABEL[$jenis])) {
            return self::JENIS_LABEL[$jenis];
        }

        $label = ucwords(str_replace('_', ' ', $jenis));

        return str_replace(['Pc', 'Iot'], ['PC', 'IoT'], $label);
    }

    /**
     * Lokasi penyimpanan dengan fallback jika admin belum mengisinya.
     */
    public function lokasiLabel(): string
    {
        if (filled($this->lokasi)) {
            return $this->lokasi;
        }

        return match ($this->jenis) {
            'IoT' => 'Lab IoT',
            'laptop', 'bagian_pc' => 'Lab Komputer',
            default => 'Lab Jaringan',
        };
    }

    /**
     * Total unit fisik dari seluruh kondisi.
     */
    public function totalFisik(): int
    {
        return (int) $this->kondisi_baik
            + (int) $this->kondisi_pengecekan
            + (int) $this->kondisi_rusak
            + (int) $this->kondisi_maintenance;
    }
}
