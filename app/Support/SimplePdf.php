<?php

namespace App\Support;

/**
 * Penulis PDF sederhana tanpa dependency eksternal.
 * Mendukung teks (Helvetica), garis, kotak, dan pemenggalan kata.
 */
class SimplePdf
{
    public float $pageWidth = 595.28;
    public float $pageHeight = 841.89;
    public float $margin = 40.0;

    private array $pages = [];
    private string $buffer = '';
    private bool $started = false;
    private float $y = 0.0;
    private bool $bold = false;
    private float $size = 10.0;

    private array $fill = [0.0, 0.0, 0.0];
    private array $stroke = [0.0, 0.0, 0.0];
    private float $lineWidth = 0.6;
    private int $lineCap = 0;

    private string $footerLeft = '';
    private bool $pageNumbers = true;

    public function __construct()
    {
        $this->resetPage();
    }

    private function resetPage(): void
    {
        $this->buffer = '';
        $this->y = $this->margin;
        $this->started = true;
    }

    public function addPage(): void
    {
        if ($this->started && trim($this->buffer) !== '') {
            $this->pages[] = $this->buffer;
        }

        $this->resetPage();
    }

    public function setFooter(string $left): void
    {
        $this->footerLeft = $left;
    }

    public function setPageNumbers(bool $show): void
    {
        $this->pageNumbers = $show;
    }

    public function cursor(): float
    {
        return $this->y;
    }

    public function moveTo(float $y): void
    {
        $this->y = $y;
    }

    public function advance(float $dy): void
    {
        $this->y += $dy;
    }

    public function contentWidth(): float
    {
        return $this->pageWidth - (2 * $this->margin);
    }

    public function bottomLimit(): float
    {
        return $this->pageHeight - $this->margin - 24;
    }

    public function need(float $height): bool
    {
        return $this->y + $height > $this->bottomLimit();
    }

    public function ensure(float $height): bool
    {
        if ($this->need($height)) {
            $this->addPage();

            return true;
        }

        return false;
    }

    public function setFont(bool $bold, float $size): void
    {
        $this->bold = $bold;
        $this->size = $size;
    }

    public function fillColor(float $r, float $g, float $b): void
    {
        $this->fill = [$r, $g, $b];
    }

    public function strokeColor(float $r, float $g, float $b): void
    {
        $this->stroke = [$r, $g, $b];
    }

    public function lineWidth(float $width): void
    {
        $this->lineWidth = $width;
    }

    /**
     * 0 = ujung datar, 1 = ujung membulat, 2 = ujung menonjol.
     */
    public function lineCap(int $cap): void
    {
        $this->lineCap = max(0, min(2, $cap));
    }

    public function text(float $x, float $y, string $text): void
    {
        $font = $this->bold ? 'F2' : 'F1';
        $py = $this->pageHeight - $y;

        $this->buffer .= $this->fillCommand();
        $this->buffer .= sprintf(
            "BT /%s %s Tf %s %s Td (%s) Tj ET\n",
            $font,
            $this->n($this->size),
            $this->n($x),
            $this->n($py),
            $this->escape($text)
        );
    }

    public function textRight(float $right, float $y, string $text): void
    {
        $this->text($right - $this->widthOf($text), $y, $text);
    }

    public function textCenter(float $center, float $y, string $text): void
    {
        $this->text($center - ($this->widthOf($text) / 2), $y, $text);
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->buffer .= $this->strokeCommand();
        $this->buffer .= sprintf("%s w\n%d J\n", $this->n($this->lineWidth), $this->lineCap);
        $this->buffer .= sprintf(
            "%s %s m %s %s l S\n",
            $this->n($x1),
            $this->n($this->pageHeight - $y1),
            $this->n($x2),
            $this->n($this->pageHeight - $y2)
        );
    }

    /**
     * Kotak dengan sudut membulat. Koordinat memakai acuan atas seperti rect().
     */
    public function roundedRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $radius,
        bool $fill = false,
        bool $stroke = false
    ): void {
        $radius = max(0.0, min($radius, min($width, $height) / 2));

        if ($radius <= 0.01) {
            $this->rect($x, $y, $width, $height, $fill, $stroke);

            return;
        }

        $k = 0.5522847498;
        $c = $radius * $k;

        $left = $x;
        $right = $x + $width;
        $bottom = $this->pageHeight - $y - $height;
        $top = $this->pageHeight - $y;

        if ($fill) {
            $this->buffer .= $this->fillCommand();
        }

        if ($stroke) {
            $this->buffer .= $this->strokeCommand();
            $this->buffer .= sprintf("%s w\n%d J\n", $this->n($this->lineWidth), $this->lineCap);
        }

        $operator = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');

        $this->buffer .= sprintf(
            "%s %s m\n"
            . "%s %s l\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s l\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s l\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s l\n"
            . "%s %s %s %s %s %s c\n"
            . "h %s\n",
            $this->n($left + $radius), $this->n($bottom),
            $this->n($right - $radius), $this->n($bottom),
            $this->n($right - $radius + $c), $this->n($bottom),
            $this->n($right), $this->n($bottom + $radius - $c),
            $this->n($right), $this->n($bottom + $radius),
            $this->n($right), $this->n($top - $radius),
            $this->n($right), $this->n($top - $radius + $c),
            $this->n($right - $radius + $c), $this->n($top),
            $this->n($right - $radius), $this->n($top),
            $this->n($left + $radius), $this->n($top),
            $this->n($left + $radius - $c), $this->n($top),
            $this->n($left), $this->n($top - $radius + $c),
            $this->n($left), $this->n($top - $radius),
            $this->n($left), $this->n($bottom + $radius),
            $this->n($left), $this->n($bottom + $radius - $c),
            $this->n($left + $radius - $c), $this->n($bottom),
            $this->n($left + $radius), $this->n($bottom),
            $operator
        );
    }

    /**
     * Lingkaran memakai empat kurva Bezier. Koordinat pusat memakai acuan atas.
     */
    public function circle(float $centerX, float $centerY, float $radius, bool $fill = false, bool $stroke = false): void
    {
        if ($radius <= 0.01) {
            return;
        }

        $k = 0.5522847498;
        $c = $radius * $k;
        $x = $centerX;
        $y = $this->pageHeight - $centerY;

        if ($fill) {
            $this->buffer .= $this->fillCommand();
        }

        if ($stroke) {
            $this->buffer .= $this->strokeCommand();
            $this->buffer .= sprintf("%s w\n%d J\n", $this->n($this->lineWidth), $this->lineCap);
        }

        $operator = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');

        $this->buffer .= sprintf(
            "%s %s m\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s %s %s %s %s c\n"
            . "%s %s %s %s %s %s c\n"
            . "h %s\n",
            $this->n($x + $radius), $this->n($y),
            $this->n($x + $radius), $this->n($y + $c),
            $this->n($x + $c), $this->n($y + $radius),
            $this->n($x), $this->n($y + $radius),
            $this->n($x - $c), $this->n($y + $radius),
            $this->n($x - $radius), $this->n($y + $c),
            $this->n($x - $radius), $this->n($y),
            $this->n($x - $radius), $this->n($y - $c),
            $this->n($x - $c), $this->n($y - $radius),
            $this->n($x), $this->n($y - $radius),
            $this->n($x + $c), $this->n($y - $radius),
            $this->n($x + $radius), $this->n($y - $c),
            $this->n($x + $radius), $this->n($y),
            $operator
        );
    }

    public function rect(float $x, float $y, float $width, float $height, bool $fill = false, bool $stroke = false): void
    {
        $py = $this->pageHeight - $y - $height;

        if ($fill) {
            $this->buffer .= $this->fillCommand();
        }

        if ($stroke) {
            $this->buffer .= $this->strokeCommand();
            $this->buffer .= sprintf("%s w\n", $this->n($this->lineWidth));
        }

        $operator = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');

        $this->buffer .= sprintf(
            "%s %s %s %s re %s\n",
            $this->n($x),
            $this->n($py),
            $this->n($width),
            $this->n($height),
            $operator
        );
    }

    public function widthOf(string $text): float
    {
        $length = function_exists('mb_strlen')
            ? mb_strlen($text, 'UTF-8')
            : strlen($text);

        $factor = $this->bold ? 0.56 : 0.52;

        return $length * $this->size * $factor;
    }

    public function wrap(string $text, float $width): array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return [''];
        }

        $words = explode(' ', $text);
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if ($this->widthOf($candidate) <= $width) {
                $line = $candidate;
                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }

            if ($this->widthOf($word) <= $width) {
                $line = $word;
                continue;
            }

            // Kata sangat panjang: pecah per karakter.
            $chars = function_exists('mb_str_split')
                ? mb_str_split($word)
                : str_split($word);

            $chunk = '';

            foreach ($chars as $char) {
                $try = $chunk . $char;

                if ($chunk !== '' && $this->widthOf($try) > $width) {
                    $lines[] = $chunk;
                    $chunk = $char;
                } else {
                    $chunk = $try;
                }
            }

            $line = $chunk;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    public function output(): string
    {
        if ($this->started) {
            $this->pages[] = $this->buffer;
        }

        $pages = $this->pages ?: [''];

        $total = count($pages);
        $prepared = [];

        foreach ($pages as $index => $content) {
            $prepared[] = $content . $this->footerCommands($index + 1, $total);
        }

        $kids = [];

        for ($i = 0; $i < $total; $i++) {
            $pageObject = 7 + (2 * $i);
            $kids[] = $pageObject . ' 0 R';
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $total . ' >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            5 => '<< /Title (' . $this->escape('Laporan Analisis SIJALAT') . ') /Author (SIJALAT) /Producer (SIJALAT) /Creator (SIJALAT) >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>',
        ];

        foreach ($prepared as $i => $content) {
            $pageObject = 7 + (2 * $i);
            $contentObject = 8 + (2 * $i);

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %s %s] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 6 0 R >> >> /Contents %d 0 R >>',
                $this->n($this->pageWidth),
                $this->n($this->pageHeight),
                $contentObject
            );

            $objects[$contentObject] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        $maxObject = max(array_keys($objects));

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        for ($id = 1; $id <= $maxObject; $id++) {
            if (! isset($objects[$id])) {
                continue;
            }

            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $objects[$id] . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);

        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= $maxObject; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R /Info 5 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function footerCommands(int $page, int $total): string
    {
        if ($this->footerLeft === '' && ! $this->pageNumbers) {
            return '';
        }

        $y = 26.0;
        $commands = '';

        $commands .= sprintf("0.45 0.50 0.55 rg\n");
        $commands .= sprintf("0.85 0.87 0.90 RG\n0.6 w\n");
        $commands .= sprintf(
            "%s %s m %s %s l S\n",
            $this->n($this->margin),
            $this->n($y + 10),
            $this->n($this->pageWidth - $this->margin),
            $this->n($y + 10)
        );

        if ($this->footerLeft !== '') {
            $commands .= sprintf(
                "BT /F1 8 Tf %s %s Td (%s) Tj ET\n",
                $this->n($this->margin),
                $this->n($y),
                $this->escape($this->footerLeft)
            );
        }

        if ($this->pageNumbers) {
            $label = 'Halaman ' . $page . ' dari ' . $total;

            $commands .= sprintf(
                "BT /F1 8 Tf %s %s Td (%s) Tj ET\n",
                $this->n($this->pageWidth - $this->margin - (strlen($label) * 8 * 0.52)),
                $this->n($y),
                $this->escape($label)
            );
        }

        return $commands;
    }

    private function fillCommand(): string
    {
        return sprintf(
            "%s %s %s rg\n",
            $this->n($this->fill[0]),
            $this->n($this->fill[1]),
            $this->n($this->fill[2])
        );
    }

    private function strokeCommand(): string
    {
        return sprintf(
            "%s %s %s RG\n",
            $this->n($this->stroke[0]),
            $this->n($this->stroke[1]),
            $this->n($this->stroke[2])
        );
    }

    private function n(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function escape(string $text): string
    {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

            if (is_string($converted)) {
                $text = $converted;
            }
        } elseif (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

            if (is_string($converted)) {
                $text = $converted;
            }
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
