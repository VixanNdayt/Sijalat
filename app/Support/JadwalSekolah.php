<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class JadwalSekolah
{
    public const HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
    ];

    /**
     * Waktu paling awal pengambilan alat pada tanggal tertentu.
     */
    public static function ambilMulai(CarbonInterface $tanggal): Carbon
    {
        return Carbon::parse($tanggal->toDateString() . ' ' . config('school.jam_ambil_mulai', '06:30'));
    }

    /**
     * Waktu terakhir pembelajaran/pengembalian pada tanggal tertentu.
     * Null berarti bukan hari sekolah (Sabtu/Minggu).
     */
    public static function kembaliTerakhir(CarbonInterface $tanggal): ?Carbon
    {
        $jam = config('school.jam_kembali_terakhir.' . $tanggal->dayOfWeekIso);

        if (! $jam) {
            return null;
        }

        return Carbon::parse($tanggal->toDateString() . ' ' . $jam);
    }

    public static function hariSekolah(CarbonInterface $tanggal): bool
    {
        return $tanggal->dayOfWeekIso >= 1 && $tanggal->dayOfWeekIso <= 5;
    }

    /**
     * Data jadwal untuk form dan JavaScript.
     */
    public static function ringkasan(): array
    {
        return [
            'ambil_mulai' => config('school.jam_ambil_mulai', '06:30'),
            'kembali_terakhir' => config('school.jam_kembali_terakhir', []),
            'label' => self::labelKembali(),
        ];
    }

    /**
     * Contoh: "Senin 16.30, Selasa–Kamis 16.35, Jumat 14.45".
     */
    public static function labelKembali(): string
    {
        $jadwal = config('school.jam_kembali_terakhir', []);
        $grup = [];
        $urutan = [];

        foreach ($jadwal as $hari => $jam) {
            $jam = (string) $jam;

            if (! isset($grup[$jam])) {
                $grup[$jam] = [];
                $urutan[] = $jam;
            }

            $grup[$jam][] = self::HARI[$hari] ?? (string) $hari;
        }

        $bagian = [];

        foreach ($urutan as $jam) {
            $hari = $grup[$jam];
            $labelHari = count($hari) > 1
                ? $hari[0] . '–' . $hari[count($hari) - 1]
                : $hari[0];

            $bagian[] = $labelHari . ' ' . str_replace(':', '.', $jam);
        }

        return implode(', ', $bagian);
    }
}
