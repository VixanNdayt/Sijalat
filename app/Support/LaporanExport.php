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
        $pdf->textRight($width - $margin, 42, 'LAPORAN ANALISIS');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(0.80, 0.93, 0.88);
        $pdf->textRight($width - $margin, 57, 'SMK Negeri 2 Depok');

        // --- Judul ---
        $pdf->moveTo(122);

        $pdf->setFont(true, 16);
        $pdf->fillColor(0.06, 0.10, 0.16);
        $pdf->text($margin, $pdf->cursor(), 'Laporan Analisis & Inventaris');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(0.42, 0.47, 0.55);
        $pdf->text(
            $margin,
            $pdf->cursor() + 17,
            'Dicetak: ' . Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i') . ' WIB'
            . '   |   Periode data: ' . self::periodeText($periode)
        );

        $pdf->strokeColor(0.86, 0.89, 0.93);
        $pdf->lineWidth(0.6);
        $pdf->line($margin, $pdf->cursor() + 25, $width - $margin, $pdf->cursor() + 25);

        $pdf->moveTo(162);

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
        $pdf->ensure(80);

        $y = $pdf->cursor();

        $pdf->fillColor(0.91, 0.96, 0.93);
        $pdf->rect($pdf->margin, $y, $pdf->contentWidth(), 20, true);

        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->rect($pdf->margin, $y, 3.5, 20, true);

        $pdf->setFont(true, 10);
        $pdf->fillColor(0.02, 0.37, 0.27);
        $pdf->text($pdf->margin + 12, $y + 14, $title);

        $pdf->moveTo($y + 30);
    }

    private static function kpiGrid(SimplePdf $pdf, array $items): void
    {
        $gap = 12.0;
        $boxWidth = ($pdf->contentWidth() - $gap) / 2;
        $boxHeight = 44.0;

        $pdf->ensure((2 * $boxHeight) + $gap + 8);

        $startY = $pdf->cursor();

        foreach ($items as $index => $item) {
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
            $pdf->text($x + 12, $y + 16, strtoupper($item[0]));

            $pdf->setFont(true, 11);
            $pdf->fillColor(0.06, 0.10, 0.16);

            if ($pdf->widthOf($item[1]) > $boxWidth - 24) {
                $pdf->setFont(true, 9.5);
            }

            $pdf->text($x + 12, $y + 33, $item[1]);
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

        $pdf->ensure($headerHeight + $rowHeight + 6);

        self::tableHeader($pdf, $headers, $widths);

        if (! $rows) {
            $y = $pdf->cursor();
            $fullWidth = $pdf->contentWidth();

            $pdf->strokeColor(0.85, 0.88, 0.92);
            $pdf->lineWidth(0.5);
            $pdf->rect($pdf->margin, $y, $fullWidth, $rowHeight, false, true);

            $pdf->setFont(false, 8.5);
            $pdf->fillColor(0.42, 0.47, 0.55);
            $pdf->text($pdf->margin + 8, $y + 12, 'Tidak ada data');

            $pdf->moveTo($y + $rowHeight + 10);

            return;
        }

        foreach ($rows as $row) {
            $pdf->setFont(false, 8.5);

            $lines = [];
            $maxLines = 1;

            foreach (array_values($row) as $index => $value) {
                $cellWidth = $widths[$index] ?? 80;
                $wrapped = $pdf->wrap((string) $value, $cellWidth - 10);
                $lines[$index] = $wrapped;
                $maxLines = max($maxLines, count($wrapped));
            }

            $height = max($rowHeight, 7 + ($maxLines * 10.5));

            if ($pdf->need($height)) {
                $pdf->addPage();
                self::tableHeader($pdf, $headers, $widths);
            }

            $y = $pdf->cursor();
            $x = $pdf->margin;

            foreach (array_values($row) as $index => $value) {
                $cellWidth = $widths[$index] ?? 80;

                $pdf->strokeColor(0.85, 0.88, 0.92);
                $pdf->lineWidth(0.5);
                $pdf->rect($x, $y, $cellWidth, $height, false, true);

                $pdf->setFont(false, 8.5);
                $pdf->fillColor(0.18, 0.23, 0.30);

                $textX = $x + 5;

                if (($align[$index] ?? 'left') === 'center') {
                    $firstLine = $lines[$index][0] ?? '';
                    $textX = $x + (($cellWidth - $pdf->widthOf($firstLine)) / 2);
                }

                $textY = $y + 12;

                foreach ($lines[$index] as $line) {
                    $drawX = $textX;

                    if (($align[$index] ?? 'left') === 'left') {
                        $drawX = $x + 5;
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

            $pdf->fillColor(0.0, 0.36, 0.26);
            $pdf->rect($x, $y, $cellWidth, 20, true);

            $pdf->setFont(true, 8.5);
            $pdf->fillColor(1, 1, 1);

            $lines = $pdf->wrap($header, $cellWidth - 8);
            $pdf->text($x + 5, $y + 13, $lines[0] ?? $header);

            $x += $cellWidth;
        }

        $pdf->moveTo($y + 20);
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
