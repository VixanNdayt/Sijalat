<?php

namespace App\Support;

use App\Models\Pinjam;
use Carbon\Carbon;

/**
 * Menyusun bukti / rincian satu transaksi peminjaman dalam format PDF.
 */
class RiwayatExport
{
    public static function pdf(Pinjam $pinjam): string
    {
        $pinjam->loadMissing(['user', 'alat']);

        $pdf = new SimplePdf();
        $pdf->setFooter('SIJALAT - Sistem Peminjaman Alat SIJA');

        $width = $pdf->pageWidth;
        $margin = $pdf->margin;

        // --- Header ---
        $pdf->fillColor(0.0, 0.36, 0.26);
        $pdf->rect(0, 0, $width, 92, true);

        $pdf->fillColor(0.06, 0.72, 0.51);
        $pdf->rect(0, 92, $width, 4, true);

        // Logo sederhana
        $pdf->fillColor(1, 1, 1);
        $pdf->rect($margin, 26, 26, 26, true);
        $pdf->fillColor(0.0, 0.36, 0.26);
        $pdf->rect($margin + 5, 31, 6, 6, true);
        $pdf->rect($margin + 15, 31, 6, 6, true);
        $pdf->rect($margin + 5, 41, 16, 4, true);

        $pdf->setFont(true, 21);
        $pdf->fillColor(1, 1, 1);
        $pdf->text($margin + 36, 46, 'SIJALAT');

        $pdf->setFont(false, 9);
        $pdf->fillColor(0.80, 0.93, 0.88);
        $pdf->text($margin + 36, 60, 'Sistem Peminjaman Alat SIJA');

        $pdf->setFont(true, 9.5);
        $pdf->fillColor(1, 1, 1);
        $pdf->textRight($width - $margin, 42, 'BUKTI RIWAYAT');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(0.80, 0.93, 0.88);
        $pdf->textRight($width - $margin, 57, 'SMK Negeri 2 Depok');

        // --- Judul ---
        $pdf->moveTo(122);

        $pdf->setFont(true, 16);
        $pdf->fillColor(0.06, 0.10, 0.16);
        $pdf->text($margin, $pdf->cursor(), 'Rincian Peminjaman Alat');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(0.42, 0.47, 0.55);
        $pdf->text(
            $margin,
            $pdf->cursor() + 17,
            'Kode: ' . ($pinjam->kode_pinjam ?: '-')
            . '   |   Dicetak: ' . Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') . ' WIB'
        );

        $pdf->strokeColor(0.86, 0.89, 0.93);
        $pdf->lineWidth(0.6);
        $pdf->line($margin, $pdf->cursor() + 25, $width - $margin, $pdf->cursor() + 25);

        $pdf->moveTo(152);

        // --- Informasi Peminjam ---
        self::sectionTitle($pdf, 'Informasi Peminjam');

        self::infoGrid($pdf, [
            ['Nama Siswa', optional($pinjam->user)->name ?? '-'],
            ['NIS', optional($pinjam->user)->nis ?? '-'],
            ['Kelas', optional($pinjam->user)->kelas ?? '-'],
            ['Status', $pinjam->status_label],
        ]);

        // --- Detail Alat & Jadwal ---
        self::sectionTitle($pdf, 'Detail Alat & Jadwal');

        self::infoGrid($pdf, [
            ['Nama Alat', optional($pinjam->alat)->nama ?? '-'],
            ['Kode Alat', optional($pinjam->alat)->code ?? '-'],
            ['Jenis', self::rapikanJenis(optional($pinjam->alat)->jenis)],
            ['Jumlah', ((int) $pinjam->jumlah_minta) . ' unit'],
            ['Ruangan', $pinjam->ruangan ?: '-'],
            ['Mulai Pinjam', self::waktu($pinjam->dipinjam_saat)],
            ['Rencana Kembali', self::waktu($pinjam->dikembalikan_saat)],
            ['Aktual Kembali', self::waktuServer($pinjam->dikembalikan_aktual)],
        ]);

        // --- Keperluan & Catatan ---
        self::sectionTitle($pdf, 'Keperluan & Catatan');

        self::field($pdf, 'Keperluan Peminjaman', $pinjam->deskripsi_minta ?: '-');
        self::field($pdf, 'Catatan Saat Pengambilan', $pinjam->catatan ?: '-');
        self::field($pdf, 'Kondisi Saat Dikembalikan', $pinjam->kondisi_kembali_label ?: 'Belum dikembalikan');
        self::field($pdf, 'Catatan Saat Pengembalian', $pinjam->catatan_kembali ?: '-');

        // --- Kotak status ---
        $pdf->ensure(56);

        $y = $pdf->cursor() + 2;
        [$bg, $fg] = self::statusColor((string) $pinjam->status);

        $pdf->fillColor($bg[0], $bg[1], $bg[2]);
        $pdf->rect($margin, $y, $pdf->contentWidth(), 36, true);

        $pdf->setFont(true, 8);
        $pdf->fillColor($fg[0], $fg[1], $fg[2]);
        $pdf->text($margin + 14, $y + 14, 'STATUS AKHIR TRANSAKSI');

        $pdf->setFont(true, 13);
        $pdf->text($margin + 14, $y + 29, $pinjam->status_label);

        $pdf->setFont(false, 7.5);
        $pdf->fillColor(0.55, 0.60, 0.66);
        $pdf->text(
            $margin,
            $y + 52,
            'Dokumen ini dibuat otomatis oleh SIJALAT dan dapat digunakan sebagai bukti riwayat peminjaman.'
        );

        return $pdf->output();
    }

    private static function sectionTitle(SimplePdf $pdf, string $title): void
    {
        $pdf->ensure(56);

        $y = $pdf->cursor();

        $pdf->fillColor(0.91, 0.96, 0.93);
        $pdf->rect($pdf->margin, $y, $pdf->contentWidth(), 20, true);

        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->rect($pdf->margin, $y, 3.5, 20, true);

        $pdf->setFont(true, 10.5);
        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->text($pdf->margin + 12, $y + 14, $title);

        $pdf->moveTo($y + 28);
    }

    private static function infoGrid(SimplePdf $pdf, array $items): void
    {
        $gap = 9.0;
        $boxWidth = ($pdf->contentWidth() - $gap) / 2;
        $boxHeight = 36.0;
        $rows = (int) ceil(count($items) / 2);

        $pdf->ensure(($rows * $boxHeight) + (($rows - 1) * $gap) + 6);

        $startY = $pdf->cursor();

        foreach ($items as $index => [$label, $value]) {
            $column = $index % 2;
            $row = intdiv($index, 2);

            $x = $pdf->margin + ($column * ($boxWidth + $gap));
            $y = $startY + ($row * ($boxHeight + $gap));

            $pdf->fillColor(0.96, 0.98, 0.99);
            $pdf->strokeColor(0.86, 0.89, 0.93);
            $pdf->lineWidth(0.5);
            $pdf->rect($x, $y, $boxWidth, $boxHeight, true, true);

            $pdf->setFont(true, 7.5);
            $pdf->fillColor(0.40, 0.45, 0.52);
            $pdf->text($x + 11, $y + 13, strtoupper($label));

            $value = (string) ($value === '' || $value === null ? '-' : $value);
            $pdf->setFont(false, 10);
            $pdf->fillColor(0.06, 0.10, 0.16);

            if ($pdf->widthOf($value) > $boxWidth - 22) {
                $pdf->setFont(false, 8.5);
            }

            $lines = $pdf->wrap($value, $boxWidth - 22);

            foreach (array_slice($lines, 0, 2) as $offset => $line) {
                $pdf->text($x + 11, $y + 25 + ($offset * 9.5), $line);
            }
        }

        $pdf->moveTo($startY + ($rows * $boxHeight) + (($rows - 1) * $gap) + 8);
    }

    private static function field(SimplePdf $pdf, string $label, string $value): void
    {
        $pdf->ensure(30);

        $pdf->setFont(true, 8);
        $pdf->fillColor(0.40, 0.45, 0.52);
        $pdf->text($pdf->margin, $pdf->cursor(), strtoupper($label));

        $pdf->advance(11);

        $lines = $pdf->wrap($value !== '' ? $value : '-', $pdf->contentWidth() - 4);

        foreach ($lines as $line) {
            $pdf->ensure(13);

            $pdf->setFont(false, 9.5);
            $pdf->fillColor(0.18, 0.23, 0.30);
            $pdf->text($pdf->margin, $pdf->cursor(), $line);
            $pdf->advance(11);
        }

        $pdf->advance(4.5);
    }

    private static function waktu($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d F Y, H.i') . ' WIB';
        } catch (\Throwable) {
            return '-';
        }
    }

    /**
     * Waktu yang disimpan server (UTC) ditampilkan dalam zona waktu Indonesia.
     */
    private static function waktuServer($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)
                ->timezone('Asia/Jakarta')
                ->locale('id')
                ->translatedFormat('d F Y, H.i') . ' WIB';
        } catch (\Throwable) {
            return '-';
        }
    }

    private static function rapikanJenis(?string $jenis): string
    {
        if (empty($jenis)) {
            return '-';
        }

        $label = ucwords(str_replace('_', ' ', $jenis));

        return str_replace(['Pc', 'Iot'], ['PC', 'IoT'], $label);
    }

    /**
     * @return array{0: array{0: float, 1: float, 2: float}, 1: array{0: float, 1: float, 2: float}}
     */
    private static function statusColor(string $status): array
    {
        return match ($status) {
            'disetuji' => [[0.86, 0.92, 0.99], [0.12, 0.25, 0.69]],
            'dipinjam' => [[0.88, 0.95, 1.0], [0.03, 0.35, 0.52]],
            'dikembalikan' => [[0.86, 0.99, 0.91], [0.09, 0.40, 0.20]],
            'ditolak' => [[0.99, 0.89, 0.89], [0.73, 0.11, 0.11]],
            'terlambat' => [[0.99, 0.95, 0.78], [0.57, 0.25, 0.05]],
            default => [[0.99, 0.95, 0.78], [0.57, 0.25, 0.05]],
        };
    }
}
