<?php

namespace App\Support;

/**
 * Header dan logo SIJALAT untuk seluruh ekspor PDF.
 *
 * Logo digambar dari bentuk dasar PDF (kotak membulat, garis, dan lingkaran)
 * supaya tidak bergantung pada gambar eksternal atau library tambahan.
 */
class PdfBrand
{
    public const INK = [0.06, 0.10, 0.16];
    public const GREEN = [0.0, 0.36, 0.26];
    public const GREEN_DARK = [0.0, 0.30, 0.22];
    public const GREEN_SOFT = [0.91, 0.96, 0.93];
    public const MINT = [0.06, 0.72, 0.51];
    public const MUTED = [0.42, 0.47, 0.55];
    public const LINE = [0.86, 0.89, 0.93];

    /**
     * Header utama halaman pertama.
     */
    public static function header(SimplePdf $pdf, string $documentType, string $subtitle = 'SMK Negeri 2 Depok'): void
    {
        $width = $pdf->pageWidth;
        $margin = $pdf->margin;

        $pdf->fillColor(...self::GREEN_DARK);
        $pdf->rect(0, 0, $width, 100, true);

        // Aksen dekoratif lembut di sisi kanan, tetap di dalam area header.
        $pdf->fillColor(0.03, 0.39, 0.29);
        $pdf->circle($width - 34, 2, 50, true);

        $pdf->fillColor(...self::MINT);
        $pdf->rect(0, 100, $width, 4, true);

        self::mark($pdf, $margin, 26, 40, true);

        $pdf->setFont(true, 22);
        $pdf->fillColor(1, 1, 1);
        $pdf->text($margin + 52, 47, 'SIJALAT');

        $pdf->setFont(false, 9);
        $pdf->fillColor(0.78, 0.92, 0.86);
        $pdf->text($margin + 52, 61, 'Sistem Peminjaman Alat SIJA');

        $pdf->setFont(false, 7.5);
        $pdf->fillColor(0.51, 0.82, 0.72);
        $pdf->text($margin + 52, 73, 'sijalat.my.id');

        $pdf->setFont(true, 8.5);
        $badgeWidth = max(132.0, $pdf->widthOf($documentType) + 26);
        $badgeX = $width - $margin - $badgeWidth;

        $pdf->fillColor(0.06, 0.48, 0.36);
        $pdf->roundedRect($badgeX, 28, $badgeWidth, 21, 10.5, true);

        $pdf->setFont(true, 8.5);
        $pdf->fillColor(1, 1, 1);
        $pdf->text($badgeX + 13, 42.5, $documentType);

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(0.78, 0.92, 0.86);
        $pdf->textRight($width - $margin, 66, $subtitle);
    }

    /**
     * Header ringkas untuk halaman lanjutan tabel.
     */
    public static function continuation(SimplePdf $pdf, string $documentType = 'LAPORAN ANALISIS'): void
    {
        $width = $pdf->pageWidth;
        $margin = $pdf->margin;

        $pdf->fillColor(...self::GREEN_DARK);
        $pdf->rect(0, 0, $width, 38, true);

        $pdf->fillColor(...self::MINT);
        $pdf->rect(0, 38, $width, 3, true);

        self::mark($pdf, $margin, 8, 22, true);

        $pdf->setFont(true, 10.5);
        $pdf->fillColor(1, 1, 1);
        $pdf->text($margin + 30, 23, 'SIJALAT');

        $pdf->setFont(false, 8);
        $pdf->fillColor(0.78, 0.92, 0.86);
        $pdf->textRight($width - $margin, 23, $documentType);

        $pdf->moveTo(58);
    }

    /**
     * Logo kotak QR dengan centang. `onDark` menghasilkan tile putih untuk
     * header gelap; `false` menghasilkan tile hijau untuk latar terang.
     */
    public static function mark(SimplePdf $pdf, float $x, float $y, float $size, bool $onDark = true): void
    {
        $radius = $size * 0.28;

        if ($onDark) {
            $pdf->fillColor(1, 1, 1);
            $pdf->roundedRect($x, $y, $size, $size, $radius, true);
            $markColor = self::GREEN;
            $checkColor = self::MINT;
        } else {
            $pdf->fillColor(...self::GREEN);
            $pdf->roundedRect($x, $y, $size, $size, $radius, true);
            $markColor = [1, 1, 1];
            $checkColor = [0.62, 0.95, 0.80];
        }

        $pad = $size * 0.20;
        $cell = $size * 0.18;
        $gap = $size * 0.08;

        $pdf->strokeColor(...$markColor);
        $pdf->lineWidth(max(1.2, $size * 0.055));
        $pdf->lineCap(1);

        $corners = [
            [$x + $pad, $y + $pad],
            [$x + $pad + $cell + $gap, $y + $pad],
            [$x + $pad, $y + $pad + $cell + $gap],
        ];

        foreach ($corners as [$cx, $cy]) {
            $pdf->roundedRect($cx, $cy, $cell, $cell, $cell * 0.22, false, true);
        }

        $pdf->strokeColor(...$checkColor);
        $pdf->lineWidth(max(1.4, $size * 0.075));
        $pdf->lineCap(1);
        $pdf->line(
            $x + ($size * 0.55),
            $y + ($size * 0.64),
            $x + ($size * 0.67),
            $y + ($size * 0.76)
        );
        $pdf->line(
            $x + ($size * 0.67),
            $y + ($size * 0.76),
            $x + ($size * 0.85),
            $y + ($size * 0.44)
        );
    }
}
