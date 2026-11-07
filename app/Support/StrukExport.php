<?php

namespace App\Support;

use App\Models\Pinjam;
use Carbon\Carbon;

/**
 * Struk transaksi bergaya nota toko (lebar 80 mm) untuk satu peminjaman.
 */
class StrukExport
{
    private const LEBAR = 226.77; // 80 mm
    private const MARGIN = 14.0;

    public static function pdf(Pinjam $pinjam): string
    {
        $pinjam->loadMissing(['user', 'alat']);

        // Ukur tinggi konten dengan kanvas panjang, lalu render ulang
        // dengan tinggi akhir agar hasil PDF pas seperti kertas struk.
        $probe = self::build($pinjam, 1400.0);
        $tinggi = max(320.0, $probe->cursor() + 16.0);

        return self::build($pinjam, $tinggi)->output();
    }

    private static function build(Pinjam $pinjam, float $pageHeight): SimplePdf
    {
        $pdf = new SimplePdf();
        $pdf->pageWidth = self::LEBAR;
        $pdf->pageHeight = $pageHeight;
        $pdf->margin = self::MARGIN;
        $pdf->setPageNumbers(false);
        $pdf->setFooter('');

        $center = self::LEBAR / 2;
        $right = self::LEBAR - self::MARGIN;

        // Aksen atas
        $pdf->fillColor(...PdfBrand::GREEN);
        $pdf->rect(0, 0, self::LEBAR, 5, true);

        // Logo dan kepala struk
        PdfBrand::mark($pdf, $center - 12, 12, 24, false);

        $pdf->setFont(true, 15);
        $pdf->fillColor(0.02, 0.29, 0.20);
        $pdf->textCenter($center, 52, 'SIJALAT');

        $pdf->setFont(false, 6.4);
        $pdf->fillColor(0.38, 0.44, 0.50);
        $pdf->textCenter($center, 62, 'SISTEM PEMINJAMAN ALAT SIJA');
        $pdf->textCenter($center, 71, 'SMK NEGERI 2 DEPOK');

        self::dashed($pdf, 80);

        $pdf->setFont(true, 9);
        $pdf->fillColor(0.06, 0.10, 0.16);
        $pdf->textCenter($center, 94, 'STRUK TRANSAKSI');

        $pdf->setFont(true, 7.2);
        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->textCenter($center, 105, $pinjam->kode_pinjam ?: 'SJL-' . $pinjam->id);

        $pdf->setFont(false, 6.2);
        $pdf->fillColor(0.45, 0.50, 0.55);
        $pdf->textCenter(
            $center,
            114,
            'Dicetak ' . Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d/m/Y H.i') . ' WIB'
        );

        self::dashed($pdf, 123);

        $pdf->moveTo(137);

        // Peminjam
        self::section($pdf, 'Peminjam');
        self::row($pdf, 'Nama', optional($pinjam->user)->name ?? '-', true);
        self::row($pdf, 'NIS', optional($pinjam->user)->nis ?? '-');
        self::row($pdf, 'Kelas', optional($pinjam->user)->kelas ?: '-');

        $pdf->advance(4);

        // Alat
        self::section($pdf, 'Alat');
        self::item($pdf, optional($pinjam->alat)->nama ?? '-', optional($pinjam->alat)->code ?? '-');
        self::row($pdf, 'Jumlah', ((int) $pinjam->jumlah_minta) . ' unit', true);
        self::row($pdf, 'Jenis', self::rapikanJenis(optional($pinjam->alat)->jenis));
        self::row($pdf, 'Ruangan', $pinjam->ruangan ?: '-');

        $pdf->advance(4);

        // Jadwal
        self::section($pdf, 'Jadwal');
        self::row($pdf, 'Mulai Pinjam', self::waktu($pinjam->dipinjam_saat));
        self::row($pdf, 'Rencana Kembali', self::waktu($pinjam->dikembalikan_saat));

        if ($pinjam->dikembalikan_aktual) {
            self::row($pdf, 'Aktual Kembali', self::waktuServer($pinjam->dikembalikan_aktual), true);
        }

        if ($durasi = self::durasi($pinjam)) {
            self::row($pdf, 'Durasi', $durasi);
        }

        $pdf->advance(4);

        // Keperluan
        self::section($pdf, 'Keperluan');
        self::paragraf($pdf, $pinjam->deskripsi_minta ?: '-');

        if ($pinjam->catatan) {
            self::section($pdf, 'Catatan Pengambilan');
            self::paragraf($pdf, $pinjam->catatan);
        }

        if ($pinjam->kondisi_kembali_label) {
            self::section($pdf, 'Kondisi Saat Kembali');
            self::row($pdf, 'Kondisi', $pinjam->kondisi_kembali_label, true);

            if ($pinjam->catatan_kembali) {
                self::paragraf($pdf, $pinjam->catatan_kembali);
            }
        }

        self::dashed($pdf, $pdf->cursor() + 2);
        $pdf->advance(10);

        // Kotak status
        [$bg, $fg] = self::statusColor((string) $pinjam->status);
        $y = $pdf->cursor();

        $pdf->fillColor($bg[0], $bg[1], $bg[2]);
        $pdf->rect(self::MARGIN, $y, $pdf->contentWidth(), 32, true);

        $pdf->setFont(true, 5.8);
        $pdf->fillColor($fg[0], $fg[1], $fg[2]);
        $pdf->textCenter($center, $y + 10, 'STATUS TRANSAKSI');

        $pdf->setFont(true, 11);
        $pdf->textCenter($center, $y + 23, strtoupper($pinjam->status_label));

        $pdf->moveTo($y + 42);

        // Barcode dekoratif dari kode pinjam
        self::barcode($pdf, $pinjam);

        self::dashed($pdf, $pdf->cursor() + 2);
        $pdf->advance(11);

        $pdf->setFont(true, 7.4);
        $pdf->fillColor(0.02, 0.29, 0.20);
        $pdf->textCenter($center, $pdf->cursor(), 'Terima kasih telah menggunakan SIJALAT');

        $pdf->advance(10);

        $pdf->setFont(false, 6.2);
        $pdf->fillColor(0.45, 0.50, 0.55);
        $pdf->textCenter($center, $pdf->cursor(), 'Simpan struk ini sebagai bukti transaksi.');
        $pdf->advance(9);
        $pdf->textCenter($center, $pdf->cursor(), 'Dokumen dibuat otomatis oleh sistem.');

        return $pdf;
    }

    private static function section(SimplePdf $pdf, string $title): void
    {
        $y = $pdf->cursor();
        $label = strtoupper($title);

        $pdf->setFont(true, 6);
        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->text(self::MARGIN, $y, $label);

        $pdf->strokeColor(0.82, 0.87, 0.83);
        $pdf->lineWidth(0.4);
        $pdf->line(
            self::MARGIN + $pdf->widthOf($label) + 8,
            $y - 2,
            self::LEBAR - self::MARGIN,
            $y - 2
        );

        $pdf->advance(11);
    }

    private static function row(SimplePdf $pdf, string $label, string $value, bool $strong = false): void
    {
        $y = $pdf->cursor();
        $right = self::LEBAR - self::MARGIN;

        $pdf->setFont(false, 6.2);
        $pdf->fillColor(0.45, 0.50, 0.55);
        $pdf->text(self::MARGIN, $y, strtoupper($label));

        $available = $pdf->contentWidth() - $pdf->widthOf(strtoupper($label)) - 10;
        $value = $value !== '' ? $value : '-';

        $pdf->setFont($strong, 7.2);
        $pdf->fillColor(0.08, 0.12, 0.18);

        if ($available > 40 && $pdf->widthOf($value) <= $available) {
            $pdf->textRight($right, $y, $value);
            $pdf->advance(10.5);

            return;
        }

        $pdf->advance(9.5);

        foreach ($pdf->wrap($value, $pdf->contentWidth() - 2) as $line) {
            $pdf->textRight($right, $pdf->cursor(), $line);
            $pdf->advance(9.5);
        }
    }

    private static function item(SimplePdf $pdf, string $nama, string $kode): void
    {
        $pdf->setFont(true, 7.8);
        $pdf->fillColor(0.06, 0.10, 0.16);

        foreach ($pdf->wrap($nama, $pdf->contentWidth() - 2) as $line) {
            $pdf->text(self::MARGIN, $pdf->cursor(), $line);
            $pdf->advance(9.5);
        }

        if ($kode !== '') {
            $pdf->setFont(false, 6.2);
            $pdf->fillColor(0.45, 0.50, 0.55);
            $pdf->text(self::MARGIN, $pdf->cursor(), 'Kode: ' . $kode);
            $pdf->advance(9.5);
        }

        $pdf->advance(2);
    }

    private static function paragraf(SimplePdf $pdf, string $text): void
    {
        $pdf->setFont(false, 7);
        $pdf->fillColor(0.16, 0.21, 0.27);

        foreach ($pdf->wrap($text !== '' ? $text : '-', $pdf->contentWidth() - 2) as $line) {
            $pdf->text(self::MARGIN, $pdf->cursor(), $line);
            $pdf->advance(9.5);
        }

        $pdf->advance(3);
    }

    private static function dashed(SimplePdf $pdf, float $y): void
    {
        $pdf->strokeColor(0.74, 0.78, 0.74);
        $pdf->lineWidth(0.55);

        $x = self::MARGIN;
        $right = self::LEBAR - self::MARGIN;

        while ($x < $right) {
            $x2 = min($x + 3.0, $right);
            $pdf->line($x, $y, $x2, $y);
            $x += 5.4;
        }
    }

    private static function barcode(SimplePdf $pdf, Pinjam $pinjam): void
    {
        $raw = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) ($pinjam->kode_pinjam ?: 'SJL' . $pinjam->id)));

        if ($raw === '') {
            return;
        }

        $y = $pdf->cursor();
        $x = self::MARGIN;
        $right = self::LEBAR - self::MARGIN;
        $tinggi = 20.0;

        foreach (str_split($raw) as $char) {
            $lebar = 0.7 + ((ord($char) % 3) * 0.55);

            if ($x + $lebar > $right) {
                break;
            }

            $pdf->fillColor(0.06, 0.10, 0.16);
            $pdf->rect($x, $y, $lebar, $tinggi, true);
            $x += $lebar + 1.5;
        }

        $pdf->setFont(false, 6.3);
        $pdf->fillColor(0.38, 0.44, 0.50);
        $pdf->textCenter(self::LEBAR / 2, $y + $tinggi + 8.5, $raw);

        $pdf->moveTo($y + $tinggi + 13);
    }

    private static function waktu($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d/m/Y H.i') . ' WIB';
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
                ->translatedFormat('d/m/Y H.i') . ' WIB';
        } catch (\Throwable) {
            return '-';
        }
    }

    private static function durasi(Pinjam $pinjam): ?string
    {
        if (empty($pinjam->dipinjam_saat) || empty($pinjam->dikembalikan_aktual)) {
            return null;
        }

        try {
            $mulai = Carbon::parse($pinjam->dipinjam_saat);

            // Samakan menjadi jam dinding lokal sebelum menghitung selisih.
            $selesai = Carbon::parse(
                Carbon::parse($pinjam->dikembalikan_aktual)
                    ->timezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s')
            );

            $menit = max(0, $mulai->diffInMinutes($selesai));

            if ($menit < 60) {
                return $menit . ' menit';
            }

            if ($menit < 1440) {
                return rtrim(rtrim(number_format($menit / 60, 1, ',', '.'), '0'), ',') . ' jam';
            }

            return rtrim(rtrim(number_format($menit / 1440, 1, ',', '.'), '0'), ',') . ' hari';
        } catch (\Throwable) {
            return null;
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
