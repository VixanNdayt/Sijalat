<?php

namespace App\Support;

use App\Models\Alat;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ekspor riwayat peminjaman untuk rentang tanggal tertentu.
 *
 * Data yang diekspor hanya informasi transaksi. Foto bukti pengambilan dan
 * pengembalian sengaja tidak pernah dibaca maupun ditulis ke dalam berkas.
 */
class RiwayatPeminjamanExport
{
    private const DOKUMEN = 'RIWAYAT PEMINJAMAN';

    public static function pdf(Collection $pinjams, array $filter): string
    {
        $pdf = new SimplePdf();
        $pdf->pageWidth = 841.89;
        $pdf->pageHeight = 595.28;
        $pdf->setFooter('SIJALAT - Sistem Peminjaman Alat SIJA');

        PdfBrand::header($pdf, self::DOKUMEN, self::namaSekolah());

        $margin = $pdf->margin;

        $pdf->moveTo(126);

        $pdf->setFont(true, 17);
        $pdf->fillColor(...PdfBrand::INK);
        $pdf->text($margin, $pdf->cursor(), 'Riwayat Peminjaman Alat');

        $pdf->setFont(false, 8.5);
        $pdf->fillColor(...PdfBrand::MUTED);
        $pdf->text(
            $margin,
            $pdf->cursor() + 17,
            'Dicetak: ' . self::waktuCetak()
            . '   |   Periode: ' . self::periodeText($filter)
        );

        $pdf->strokeColor(...PdfBrand::LINE);
        $pdf->lineWidth(0.6);
        $pdf->line($margin, $pdf->cursor() + 25, $pdf->pageWidth - $margin, $pdf->cursor() + 25);

        $pdf->moveTo(168);

        self::ringkasan($pdf, $pinjams, $filter);

        LaporanExport::sectionTitle($pdf, 'Daftar Riwayat Peminjaman', self::DOKUMEN);

        $rows = [];

        foreach ($pinjams as $index => $pinjam) {
            $status = $pinjam->status_label;

            if ($pinjam->terlambat_label) {
                $status .= ' (' . $pinjam->terlambat_label . ')';
            }

            $rows[] = [
                $index + 1,
                $pinjam->kode_pinjam ?: '-',
                self::tanggal($pinjam->created_at),
                optional($pinjam->user)->name ?? '-',
                optional($pinjam->user)->kelas ?? '-',
                optional($pinjam->alat)->nama ?? '-',
                (int) $pinjam->jumlah_minta,
                optional($pinjam->guru)->nama ?? '-',
                self::tanggal($pinjam->dipinjam_saat),
                self::tanggal($pinjam->dikembalikan_saat),
                $status,
            ];
        }

        LaporanExport::table(
            $pdf,
            ['No', 'Kode', 'Diajukan', 'Siswa', 'Kelas', 'Alat', 'Jml', 'Guru', 'Mulai Pinjam', 'Tenggat', 'Status'],
            $rows,
            [24, 62, 62, 105, 52, 128, 26, 90, 62, 62, 84],
            ['center', 'left', 'left', 'left', 'left', 'left', 'center', 'left', 'left', 'left', 'left'],
            self::DOKUMEN
        );

        $pdf->advance(6);
        $pdf->setFont(false, 7.5);
        $pdf->fillColor(0.55, 0.60, 0.66);
        $pdf->text(
            $margin,
            $pdf->cursor() + 10,
            'Data berisi informasi transaksi tanpa foto bukti. Laporan dibuat otomatis oleh SIJALAT.'
        );

        return $pdf->output();
    }

    public static function excel(Collection $pinjams, array $filter): string
    {
        $xlsx = new SimpleXlsx('Riwayat Peminjaman');
        $xlsx->setColumnWidths([
            5, 15, 17, 12, 24, 12, 26, 15, 19, 7, 22, 22, 17, 17, 17, 17, 17, 19, 22, 32, 26, 26,
        ]);

        $last = 'V';

        $row = $xlsx->addRow([
            ['v' => 'RIWAYAT PEMINJAMAN ALAT SIJALAT', 's' => 'title'],
            ...array_fill(0, 21, null),
        ], 28);
        $xlsx->merge("A{$row}:{$last}{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Sistem Peminjaman Alat SIJA - ' . self::namaSekolah(), 's' => 'subtitle'],
            ...array_fill(0, 21, null),
        ], 16);
        $xlsx->merge("A{$row}:{$last}{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Dicetak: ' . self::waktuCetak(), 's' => 'meta'],
            ...array_fill(0, 21, null),
        ], 14);
        $xlsx->merge("A{$row}:{$last}{$row}");

        $row = $xlsx->addRow([
            ['v' => 'Periode: ' . self::periodeText($filter), 's' => 'meta'],
            ...array_fill(0, 21, null),
        ], 14);
        $xlsx->merge("A{$row}:{$last}{$row}");

        $xlsx->addRow([null]);

        $ringkasan = self::angkaRingkasan($pinjams);

        foreach ($ringkasan as [$label, $nilai]) {
            $row = $xlsx->addRow([
                null,
                ['v' => $label, 's' => 'label'],
                null,
                ['v' => $nilai, 's' => 'value'],
                ...array_fill(0, 18, null),
            ], 18);

            $xlsx->merge("B{$row}:C{$row}");
            $xlsx->merge("D{$row}:F{$row}");
        }

        $xlsx->addRow([null]);

        $row = $xlsx->addRow([['v' => 'DAFTAR RIWAYAT PEMINJAMAN', 's' => 'section'], ...array_fill(0, 21, null)], 20);
        $xlsx->merge("A{$row}:{$last}{$row}");

        $headers = [
            'No', 'Kode Pinjam', 'Diajukan', 'NIS', 'Nama Siswa', 'Kelas',
            'Nama Alat', 'Kode Alat', 'Jenis', 'Jumlah', 'Guru Pengampu', 'Mata Pelajaran',
            'Ruangan', 'Mulai Pinjam', 'Rencana Kembali', 'Aktual Kembali', 'Status',
            'Kondisi Kembali', 'Keterlambatan', 'Keperluan', 'Catatan Pengambilan', 'Catatan Pengembalian',
        ];

        $xlsx->addRow(array_map(
            static fn (string $header) => ['v' => $header, 's' => 'table_head'],
            $headers
        ), 28);

        if ($pinjams->isEmpty()) {
            $row = $xlsx->addRow([
                null,
                ['v' => 'Tidak ada riwayat peminjaman pada periode ini', 's' => 'text'],
                ...array_fill(0, 20, null),
            ], 18);
            $xlsx->merge("B{$row}:{$last}{$row}");

            return $xlsx->output();
        }

        foreach ($pinjams as $index => $pinjam) {
            $xlsx->addRow([
                ['v' => $index + 1, 's' => 'number', 't' => 'n'],
                ['v' => $pinjam->kode_pinjam ?: '-', 's' => 'text'],
                ['v' => self::tanggal($pinjam->created_at), 's' => 'text'],
                ['v' => optional($pinjam->user)->nis ?? '-', 's' => 'text'],
                ['v' => optional($pinjam->user)->name ?? '-', 's' => 'text_bold'],
                ['v' => optional($pinjam->user)->kelas ?? '-', 's' => 'text'],
                ['v' => optional($pinjam->alat)->nama ?? '-', 's' => 'text'],
                ['v' => optional($pinjam->alat)->code ?? '-', 's' => 'text'],
                ['v' => optional($pinjam->alat)->jenis ? Alat::jenisLabel($pinjam->alat->jenis) : '-', 's' => 'text'],
                ['v' => (int) $pinjam->jumlah_minta, 's' => 'number', 't' => 'n'],
                ['v' => optional($pinjam->guru)->nama ?? '-', 's' => 'text'],
                ['v' => optional($pinjam->mapel)->nama_mapel ?? '-', 's' => 'text'],
                ['v' => $pinjam->ruangan ?: '-', 's' => 'text'],
                ['v' => self::tanggal($pinjam->dipinjam_saat), 's' => 'text'],
                ['v' => self::tanggal($pinjam->dikembalikan_saat), 's' => 'text'],
                ['v' => self::tanggal($pinjam->dikembalikan_aktual), 's' => 'text'],
                ['v' => $pinjam->status_label, 's' => 'text'],
                ['v' => $pinjam->kondisi_kembali_label ?? '-', 's' => 'text'],
                ['v' => $pinjam->terlambat_label ?? '-', 's' => 'text'],
                ['v' => $pinjam->deskripsi_minta ?: '-', 's' => 'text'],
                ['v' => $pinjam->catatan ?: '-', 's' => 'text'],
                ['v' => $pinjam->catatan_kembali ?: '-', 's' => 'text'],
            ], 22);
        }

        return $xlsx->output();
    }

    private static function ringkasan(SimplePdf $pdf, Collection $pinjams, array $filter): void
    {
        LaporanExport::sectionTitle($pdf, 'Ringkasan Periode', self::DOKUMEN);

        $items = self::angkaRingkasan($pinjams);

        $gap = 12.0;
        $boxWidth = ($pdf->contentWidth() - $gap) / 2;
        $boxHeight = 40.0;
        $rows = (int) ceil(count($items) / 2);

        if ($pdf->ensure(($rows * $boxHeight) + (($rows - 1) * $gap) + 8)) {
            PdfBrand::continuation($pdf, self::DOKUMEN);
        }

        $startY = $pdf->cursor();

        foreach ($items as $index => [$label, $value]) {
            $column = $index % 2;
            $row = intdiv($index, 2);

            $x = $pdf->margin + ($column * ($boxWidth + $gap));
            $y = $startY + ($row * ($boxHeight + $gap));

            $pdf->fillColor(0.97, 0.985, 0.98);
            $pdf->strokeColor(...PdfBrand::LINE);
            $pdf->lineWidth(0.5);
            $pdf->roundedRect($x, $y, $boxWidth, $boxHeight, 6, true, true);

            $pdf->fillColor(...PdfBrand::GREEN);
            $pdf->roundedRect($x + 11, $y + 11, 3, $boxHeight - 22, 1.5, true);

            $pdf->setFont(true, 7.3);
            $pdf->fillColor(...PdfBrand::MUTED);
            $pdf->text($x + 20, $y + 14, strtoupper($label));

            $pdf->setFont(true, 11);
            $pdf->fillColor(...PdfBrand::INK);
            $pdf->text($x + 20, $y + 29, $value);
        }

        $pdf->moveTo($startY + ($rows * $boxHeight) + (($rows - 1) * $gap) + 10);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private static function angkaRingkasan(Collection $pinjams): array
    {
        $status = $pinjams->countBy('status');

        return [
            ['Total Transaksi', $pinjams->count() . ' transaksi'],
            ['Total Unit Dipinjam', (int) $pinjams->sum('jumlah_minta') . ' unit'],
            ['Sedang Dipinjam', (int) ($status['dipinjam'] ?? 0) . ' transaksi'],
            ['Sudah Dikembalikan', (int) ($status['dikembalikan'] ?? 0) . ' transaksi'],
            ['Menunggu Persetujuan', (int) ($status['pending'] ?? 0) . ' transaksi'],
            ['Terlambat', $pinjams->filter(fn ($pinjam) => $pinjam->terlambat_label !== null)->count() . ' transaksi'],
        ];
    }

    public static function periodeText(array $filter): string
    {
        return $filter['label'];
    }

    private static function waktuCetak(): string
    {
        return Carbon::now()
            ->timezone('Asia/Jakarta')
            ->locale('id')
            ->translatedFormat('d F Y, H.i') . ' WIB';
    }

    private static function tanggal($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)
                ->timezone('Asia/Jakarta')
                ->locale('id')
                ->translatedFormat('d/m/Y H.i');
        } catch (\Throwable) {
            return '-';
        }
    }

    private static function namaSekolah(): string
    {
        return (string) config('lab.sekolah', 'SMK Negeri 2 Yogyakarta');
    }
}
