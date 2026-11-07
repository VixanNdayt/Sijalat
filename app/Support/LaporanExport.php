<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Menyusun laporan analisis dalam format PDF dan XLSX.
 */
class LaporanExport
{
    public static function pdf(array $data, array $periode): string
    {
        $pdf = new SimplePdf();
        $pdf->setFooter('SIJALAT - Sistem Peminjaman Alat SIJA');

        $width = $pdf->pageWidth;
        $margin = $pdf->margin;

        PdfBrand::header($pdf, 'LAPORAN ANALISIS', 'SMK Negeri 2 Depok');

        // --- Judul ---
        $pdf->moveTo(126);

        $pdf->setFont(true, 17);
        $pdf->fillColor(...PdfBrand::INK);
        $pdf->text($margin, $pdf->cursor(), 'Laporan Analisis & Inventaris');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(...PdfBrand::MUTED);
        $pdf->text(
            $margin,
            $pdf->cursor() + 17,
            'Dicetak: ' . Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') . ' WIB'
            . '   |   Periode data: ' . self::periodeText($periode)
        );

        $pdf->strokeColor(...PdfBrand::LINE);
        $pdf->lineWidth(0.6);
        $pdf->line($margin, $pdf->cursor() + 25, $width - $margin, $pdf->cursor() + 25);

        $pdf->moveTo(168);

        // --- Ringkasan ---
        self::sectionTitle($pdf, 'Ringkasan Inventaris');

        self::kpiGrid($pdf, [
            ['Total Inventaris', $data['totalSKU'] . ' SKU / ' . $data['totalUnit'] . ' Unit'],
            ['Distribusi Stok', $data['stokTersedia'] . ' Ready / ' . $data['sedangDipinjam'] . ' Out'],
            ['Kondisi Fisik', 'B: ' . $data['kondisiBaik'] . ' | C: ' . $data['kondisiPengecekan'] . ' | R: ' . $data['kondisiRusak'] . ' | M: ' . $data['kondisiMtc']],
            ['Status Transaksi', $data['pendingApproval'] . ' Pending | ' . $data['activeLoans'] . ' Aktif | ' . $data['overdueCount'] . ' Terlambat'],
        ]);

        // --- Top 5 ---
        self::sectionTitle($pdf, 'Top 5 Barang Sering Dipinjam');

        $topRows = [];

        foreach ($data['topItems'] as $index => $item) {
            $topRows[] = [
                $index + 1,
                $item->nama,
                $item->total_pinjam . 'x',
            ];
        }

        self::table(
            $pdf,
            ['No', 'Nama Barang', 'Total Dipinjam'],
            $topRows,
            [34, 360, 121],
            ['center', 'left', 'center']
        );

        // --- Log aktivitas ---
        self::sectionTitle($pdf, 'Log Aktivitas Terbaru');

        $activityRows = [];

        foreach ($data['recentActivities'] as $index => $item) {
            $activityRows[] = [
                $index + 1,
                Carbon::parse($item->created_at)->format('d/m/y H:i'),
                optional($item->user)->name ?? '-',
                optional($item->alat)->nama ?? '-',
                $item->status_label,
            ];
        }

        self::table(
            $pdf,
            ['No', 'Waktu', 'Siswa', 'Barang', 'Status'],
            $activityRows,
            [34, 88, 118, 180, 95],
            ['center', 'left', 'left', 'left', 'left']
        );

        // --- Alert stok ---
        self::sectionTitle($pdf, 'Alert Stok & Kerusakan');

        $alertRows = [];

        foreach ($data['stockAlerts'] as $index => $item) {
            $alertRows[] = [
                $index + 1,
                $item->nama,
                $item->jumlah_stok,
                $item->kondisi_baik,
                $item->kondisi_pengecekan,
                $item->kondisi_rusak,
                $item->kondisi_maintenance,
            ];
        }

        self::table(
            $pdf,
            ['No', 'Nama Alat', 'Stok', 'Baik', 'Cek', 'Rusak', 'Mtc'],
            $alertRows,
            [30, 210, 50, 48, 48, 58, 60],
            ['center', 'left', 'center', 'center', 'center', 'center', 'center']
        );

        $pdf->advance(6);
        $pdf->setFont(false, 7.5);
        $pdf->fillColor(0.55, 0.60, 0.66);
        $pdf->text($margin, $pdf->cursor() + 10, 'Laporan dibuat otomatis oleh SIJALAT. Data mengikuti kondisi saat laporan dicetak.');

        return $pdf->output();
    }

    public static function excel(array $data, array $periode): string
    {
        $xlsx = new SimpleXlsx('Laporan Analisis');
        $xlsx->setColumnWidths([6, 34, 20, 26, 16, 14]);

        // Judul
        $row = $xlsx->addRow([
            ['v' => 'LAPORAN ANALISIS & INVENTARIS SIJALAT', 's' => 'title'],
            null, null, null, null, null,
        ], 28);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Sistem Peminjaman Alat SIJA - SMK Negeri 2 Depok', 's' => 'subtitle'],
            null, null, null, null, null,
        ], 16);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Dicetak: ' . Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') . ' WIB', 's' => 'meta'],
            null, null, null, null, null,
        ], 14);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Periode data: ' . self::periodeText($periode), 's' => 'meta'],
            null, null, null, null, null,
        ], 14);
        $xlsx->merge("A{$row}:F{$row}");

        $xlsx->addRow([null]);

        // Ringkasan
        $row = $xlsx->addRow([['v' => 'RINGKASAN INVENTARIS', 's' => 'section'], null, null, null, null, null], 20);
        $xlsx->merge("A{$row}:F{$row}");

        $ringkasan = [
            ['Total Inventaris', $data['totalSKU'] . ' SKU / ' . $data['totalUnit'] . ' Unit'],
            ['Distribusi Stok', $data['stokTersedia'] . ' Ready / ' . $data['sedangDipinjam'] . ' Out'],
            ['Kondisi Fisik', 'Baik: ' . $data['kondisiBaik'] . ' | Cek: ' . $data['kondisiPengecekan'] . ' | Rusak: ' . $data['kondisiRusak'] . ' | Maintenance: ' . $data['kondisiMtc']],
            ['Status Transaksi', $data['pendingApproval'] . ' Pending | ' . $data['activeLoans'] . ' Aktif | ' . $data['overdueCount'] . ' Terlambat'],
        ];

        foreach ($ringkasan as [$label, $value]) {
            $row = $xlsx->addRow([
                null,
                ['v' => $label, 's' => 'label'],
                null,
                ['v' => (string) $value, 's' => 'value'],
                null,
                null,
            ], 18);

            $xlsx->merge("B{$row}:C{$row}");
            $xlsx->merge("D{$row}:F{$row}");
        }

        // Top 5
        $xlsx->addRow([null]);

        $row = $xlsx->addRow([['v' => 'TOP 5 BARANG SERING DIPINJAM', 's' => 'section'], null, null, null, null, null], 20);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'No', 's' => 'table_head'],
            ['v' => 'Nama Barang', 's' => 'table_head'],
            ['v' => 'Total Dipinjam', 's' => 'table_head'],
            null, null, null,
        ], 20);
        $xlsx->merge("C{$row}:F{$row}");

        if ($data['topItems']->isEmpty()) {
            $row = $xlsx->addRow([
                null,
                ['v' => 'Belum ada data peminjaman', 's' => 'text'],
                null, null, null, null,
            ], 18);
            $xlsx->merge("B{$row}:F{$row}");
        } else {
            foreach ($data['topItems'] as $index => $item) {
                $row = $xlsx->addRow([
                    ['v' => $index + 1, 's' => 'number', 't' => 'n'],
                    ['v' => $item->nama, 's' => 'text_bold'],
                    ['v' => (int) $item->total_pinjam, 's' => 'number', 't' => 'n'],
                    null, null, null,
                ], 20);
                $xlsx->merge("C{$row}:F{$row}");
            }
        }

        // Log aktivitas
        $xlsx->addRow([null]);

        $row = $xlsx->addRow([['v' => 'LOG AKTIVITAS TERBARU', 's' => 'section'], null, null, null, null, null], 20);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'No', 's' => 'table_head'],
            ['v' => 'Waktu', 's' => 'table_head'],
            ['v' => 'Siswa', 's' => 'table_head'],
            ['v' => 'Barang', 's' => 'table_head'],
            ['v' => 'Status', 's' => 'table_head'],
            null,
        ], 20);

        if ($data['recentActivities']->isEmpty()) {
            $row = $xlsx->addRow([
                null,
                ['v' => 'Belum ada aktivitas', 's' => 'text'],
                null, null, null, null,
            ], 18);
            $xlsx->merge("B{$row}:F{$row}");
        } else {
            foreach ($data['recentActivities'] as $index => $item) {
                $xlsx->addRow([
                    ['v' => $index + 1, 's' => 'number', 't' => 'n'],
                    ['v' => Carbon::parse($item->created_at)->format('d/m/Y H:i'), 's' => 'text'],
                    ['v' => optional($item->user)->name ?? '-', 's' => 'text'],
                    ['v' => optional($item->alat)->nama ?? '-', 's' => 'text'],
                    ['v' => $item->status_label, 's' => 'text'],
                    null,
                ], 20);
            }
        }

        // Alert stok
        $xlsx->addRow([null]);

        $row = $xlsx->addRow([['v' => 'ALERT STOK & KERUSAKAN', 's' => 'section'], null, null, null, null, null], 20);
        $xlsx->merge("A{$row}:F{$row}");

        $row = $xlsx->addRow([
            ['v' => 'No', 's' => 'table_head'],
            ['v' => 'Nama Alat', 's' => 'table_head'],
            ['v' => 'Stok', 's' => 'table_head'],
            ['v' => 'Baik', 's' => 'table_head'],
            ['v' => 'Butuh Cek', 's' => 'table_head'],
            ['v' => 'Rusak / Mtc', 's' => 'table_head'],
        ], 20);

        if ($data['stockAlerts']->isEmpty()) {
            $row = $xlsx->addRow([
                null,
                ['v' => 'Semua stok dalam kondisi aman', 's' => 'text'],
                null, null, null, null,
            ], 18);
            $xlsx->merge("B{$row}:F{$row}");
        } else {
            foreach ($data['stockAlerts'] as $index => $item) {
                $xlsx->addRow([
                    ['v' => $index + 1, 's' => 'number', 't' => 'n'],
                    ['v' => $item->nama, 's' => 'text_bold'],
                    ['v' => (int) $item->jumlah_stok, 's' => 'number', 't' => 'n'],
                    ['v' => (int) $item->kondisi_baik, 's' => 'number', 't' => 'n'],
                    ['v' => (int) $item->kondisi_pengecekan, 's' => 'number', 't' => 'n'],
                    ['v' => (int) $item->kondisi_rusak . ' / ' . (int) $item->kondisi_maintenance, 's' => 'text'],
                ], 20);
            }
        }

        return $xlsx->output();
    }

    private static function sectionTitle(SimplePdf $pdf, string $title): void
    {
        if ($pdf->ensure(80)) {
            PdfBrand::continuation($pdf);
        }

        $y = $pdf->cursor();

        $pdf->fillColor(...PdfBrand::GREEN_SOFT);
        $pdf->roundedRect($pdf->margin, $y, $pdf->contentWidth(), 22, 6, true);

        $pdf->fillColor(...PdfBrand::GREEN);
        $pdf->roundedRect($pdf->margin, $y, 4, 22, 2, true);

        $pdf->setFont(true, 10);
        $pdf->fillColor(...PdfBrand::GREEN);
        $pdf->text($pdf->margin + 13, $y + 14.5, $title);

        $pdf->moveTo($y + 32);
    }

    private static function kpiGrid(SimplePdf $pdf, array $items): void
    {
        $gap = 12.0;
        $boxWidth = ($pdf->contentWidth() - $gap) / 2;
        $boxHeight = 48.0;

        if ($pdf->ensure((2 * $boxHeight) + $gap + 8)) {
            PdfBrand::continuation($pdf);
        }

        $startY = $pdf->cursor();
        $accents = [
            [0.02, 0.55, 0.40],
            [0.15, 0.45, 0.86],
            [0.85, 0.55, 0.12],
            [0.55, 0.36, 0.84],
        ];

        foreach ($items as $index => $item) {
            $column = $index % 2;
            $row = intdiv($index, 2);

            $x = $pdf->margin + ($column * ($boxWidth + $gap));
            $y = $startY + ($row * ($boxHeight + $gap));
            $accent = $accents[$index % count($accents)];

            $pdf->fillColor(0.98, 0.99, 0.99);
            $pdf->strokeColor(...PdfBrand::LINE);
            $pdf->lineWidth(0.5);
            $pdf->roundedRect($x, $y, $boxWidth, $boxHeight, 7, true, true);

            $pdf->fillColor($accent[0], $accent[1], $accent[2]);
            $pdf->roundedRect($x + 9, $y + 11, 3, $boxHeight - 22, 1.5, true);

            $pdf->setFont(true, 7.3);
            $pdf->fillColor(...PdfBrand::MUTED);
            $pdf->text($x + 20, $y + 16, strtoupper($item[0]));

            $pdf->setFont(true, 11);
            $pdf->fillColor(...PdfBrand::INK);

            if ($pdf->widthOf($item[1]) > $boxWidth - 32) {
                $pdf->setFont(true, 9.2);
            }

            $pdf->text($x + 20, $y + 34, $item[1]);
        }

        $pdf->moveTo($startY + (2 * $boxHeight) + $gap + 12);
    }

    private static function table(SimplePdf $pdf, array $headers, array $rows, array $widths, array $align = []): void
    {
        $total = array_sum($widths);
        $scale = $pdf->contentWidth() / $total;
        $widths = array_map(static fn ($width) => $width * $scale, $widths);

        $rowHeight = 18.0;
        $headerHeight = 20.0;

        if ($pdf->ensure($headerHeight + $rowHeight + 6)) {
            PdfBrand::continuation($pdf);
        }

        self::tableHeader($pdf, $headers, $widths);

        if (! $rows) {
            $y = $pdf->cursor();
            $fullWidth = $pdf->contentWidth();

            $pdf->strokeColor(...PdfBrand::LINE);
            $pdf->lineWidth(0.5);
            $pdf->rect($pdf->margin, $y, $fullWidth, $rowHeight, false, true);

            $pdf->setFont(false, 8.5);
            $pdf->fillColor(...PdfBrand::MUTED);
            $pdf->text($pdf->margin + 8, $y + 12, 'Tidak ada data');

            $pdf->moveTo($y + $rowHeight + 10);

            return;
        }

        foreach ($rows as $rowIndex => $row) {
            $pdf->setFont(false, 8.5);

            $lines = [];
            $maxLines = 1;

            foreach (array_values($row) as $index => $value) {
                $cellWidth = $widths[$index] ?? 80;
                $wrapped = $pdf->wrap((string) $value, $cellWidth - 10);
                $lines[$index] = $wrapped;
                $maxLines = max($maxLines, count($wrapped));
            }

            $height = max($rowHeight, 12 + ($maxLines * 10.5));

            if ($pdf->need($height)) {
                $pdf->addPage();
                PdfBrand::continuation($pdf);
                self::tableHeader($pdf, $headers, $widths);
            }

            $y = $pdf->cursor();
            $x = $pdf->margin;

            if ($rowIndex % 2 === 1) {
                $pdf->fillColor(0.97, 0.985, 0.98);
                $pdf->rect($pdf->margin, $y, $pdf->contentWidth(), $height, true);
            }

            foreach (array_values($row) as $index => $value) {
                $cellWidth = $widths[$index] ?? 80;

                $pdf->strokeColor(...PdfBrand::LINE);
                $pdf->lineWidth(0.5);
                $pdf->rect($x, $y, $cellWidth, $height, false, true);

                $pdf->setFont(false, 8.5);
                $pdf->fillColor(0.18, 0.23, 0.30);

                $textY = $y + (($height - (count($lines[$index]) * 10.5)) / 2) + 8.2;

                foreach ($lines[$index] as $line) {
                    $drawX = $x + 5;
                    $alignMode = $align[$index] ?? 'left';

                    if ($alignMode === 'center') {
                        $drawX = $x + (($cellWidth - $pdf->widthOf($line)) / 2);
                    } elseif ($alignMode === 'right') {
                        $drawX = $x + $cellWidth - $pdf->widthOf($line) - 5;
                    }

                    $pdf->text($drawX, $textY, $line);
                    $textY += 10.5;
                }

                $x += $cellWidth;
            }

            $pdf->moveTo($y + $height);
        }

        $pdf->advance(10);
    }

    private static function tableHeader(SimplePdf $pdf, array $headers, array $widths): void
    {
        $y = $pdf->cursor();
        $x = $pdf->margin;

        foreach ($headers as $index => $header) {
            $cellWidth = $widths[$index] ?? 80;

            $pdf->fillColor(...PdfBrand::GREEN);
            $pdf->rect($x, $y, $cellWidth, 20, true);

            $pdf->setFont(true, 8.5);
            $pdf->fillColor(1, 1, 1);

            $lines = $pdf->wrap($header, $cellWidth - 8);
            $pdf->text($x + 5, $y + 13, $lines[0] ?? $header);

            $x += $cellWidth;
        }

        $pdf->fillColor(...PdfBrand::MINT);
        $pdf->rect($pdf->margin, $y + 20, $pdf->contentWidth(), 1.6, true);

        $pdf->moveTo($y + 21.6);
    }

    private static function periodeText(array $periode): string
    {
        if (($periode['periode'] ?? 'semua') === 'semua') {
            return 'Semua data';
        }

        $label = (string) ($periode['label'] ?? '');

        if (! empty($periode['mulai']) && ! empty($periode['selesai'])) {
            $label .= ' (' . $periode['mulai']->format('d/m/Y') . ' - ' . $periode['selesai']->format('d/m/Y') . ')';
        }

        return $label;
    }
}
