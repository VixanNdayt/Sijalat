<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Support\LaporanExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalisisController extends Controller
{
    private const PERIODE = ['semua', 'harian', 'mingguan', 'bulanan', 'semester'];

    public function index(Request $request)
    {
        $filter = $this->periodeFilter($request);

        return view('admin.analisis', array_merge($this->laporanData($filter), [
            'periode' => $filter['periode'],
            'periodeLabel' => $filter['label'],
            'tanggal' => $filter['tanggal'],
            'mulai' => $filter['mulai'],
            'selesai' => $filter['selesai'],
        ]));
    }

    public function exportPdf(Request $request)
    {
        $filter = $this->periodeFilter($request);
        $data = $this->laporanData($filter);

        $pdf = LaporanExport::pdf($data, $filter);
        $filename = 'Laporan-SIJALAT-' . $this->namaFile($filter) . '.pdf';
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filter = $this->periodeFilter($request);
        $data = $this->laporanData($filter);

        $excel = LaporanExport::excel($data, $filter);
        $filename = 'Laporan-SIJALAT-' . $this->namaFile($filter) . '.xlsx';

        return response($excel, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($excel),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Tentukan rentang tanggal berdasarkan periode yang dipilih.
     *
     * @return array{periode: string, label: string, mulai: ?Carbon, selesai: ?Carbon, group: string, ref: Carbon, tanggal: string}
     */
    private function periodeFilter(Request $request): array
    {
        $periode = $request->input('periode', 'semua');
        $periode = is_string($periode) ? $periode : 'semua';

        if (! in_array($periode, self::PERIODE, true)) {
            $periode = 'semua';
        }

        $ref = $this->parseTanggal($request->input('tanggal')) ?? Carbon::today();

        // Kompatibilitas: ?tanggal=... tanpa periode dianggap filter harian.
        if (! $request->has('periode') && $request->filled('tanggal') && $this->parseTanggal($request->input('tanggal'))) {
            $periode = 'harian';
        }

        $mulai = null;
        $selesai = null;
        $label = 'Semua Data';
        $group = 'day';
        $tanggal = $ref->toDateString();

        switch ($periode) {
            case 'harian':
                $mulai = $ref->copy()->startOfDay();
                $selesai = $ref->copy()->endOfDay();
                $label = 'Harian - ' . $ref->locale('id')->translatedFormat('d F Y');
                break;

            case 'mingguan':
                $mulai = $ref->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $selesai = $ref->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $label = 'Mingguan - '
                    . $mulai->locale('id')->translatedFormat('d M')
                    . ' s.d. ' . $selesai->locale('id')->translatedFormat('d M Y');
                break;

            case 'bulanan':
                $mulai = $ref->copy()->startOfMonth()->startOfDay();
                $selesai = $ref->copy()->endOfMonth()->endOfDay();
                $label = 'Bulanan - ' . $ref->locale('id')->translatedFormat('F Y');
                break;

            case 'semester':
                if ((int) $ref->month >= 7) {
                    $mulai = Carbon::create((int) $ref->year, 7, 1)->startOfDay();
                    $selesai = Carbon::create((int) $ref->year, 12, 31)->endOfDay();
                    $label = 'Semester Ganjil ' . $ref->year . ' (Juli - Desember)';
                } else {
                    $mulai = Carbon::create((int) $ref->year, 1, 1)->startOfDay();
                    $selesai = Carbon::create((int) $ref->year, 6, 30)->endOfDay();
                    $label = 'Semester Genap ' . $ref->year . ' (Januari - Juni)';
                }

                $group = 'month';
                break;
        }

        return compact('periode', 'label', 'mulai', 'selesai', 'group', 'ref', 'tanggal');
    }

    private function parseTanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($nilai))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function namaFile(array $filter): string
    {
        if ($filter['periode'] === 'semua') {
            return 'semua-data';
        }

        if ($filter['periode'] === 'semester') {
            $jenis = ((int) $filter['ref']->month >= 7) ? 'ganjil' : 'genap';

            return 'semester-' . $jenis . '-' . $filter['ref']->format('Y');
        }

        return $filter['periode'] . '-' . $filter['ref']->format('Y-m-d');
    }

    private function laporanData(array $filter): array
    {
        // 1. METRIC CARDS (Total & Kondisi)
        $totalSKU = Alat::count();
        $kondisiBaik = Alat::sum('kondisi_baik');
        $kondisiPengecekan = Alat::sum('kondisi_pengecekan');
        $kondisiRusak = Alat::sum('kondisi_rusak');
        $kondisiMtc = Alat::sum('kondisi_maintenance');

        // Total unit fisik dihitung dari seluruh kondisi. Stok tersedia memakai
        // jumlah_stok yang sudah dikurangi unit yang sedang dipinjam.
        $totalUnit = $kondisiBaik + $kondisiPengecekan + $kondisiRusak + $kondisiMtc;

        // Status Peminjaman (kondisi saat ini)
        $stokTersedia = Alat::sum('jumlah_stok');
        $sedangDipinjam = Pinjam::where('status', 'dipinjam')->sum('jumlah_minta');

        $pendingApproval = Pinjam::where('status', 'pending')->count();
        $activeLoans = Pinjam::where('status', 'dipinjam')->count();

        $overdueCount = Pinjam::where('status', 'dipinjam')
            ->where('created_at', '<', Carbon::today())
            ->count();

        $rentang = $filter['mulai'] && $filter['selesai']
            ? [$filter['mulai'], $filter['selesai']]
            : null;

        // 2. GRAFIK DATA
        // A. Top 5 Barang Paling Sering Dipinjam
        $topItems = DB::table('pinjams')
            ->join('alats', 'pinjams.alat_id', '=', 'alats.id')
            ->select('alats.nama', DB::raw('COUNT(pinjams.id) as total_pinjam'))
            ->when($rentang, fn ($query) => $query->whereBetween('pinjams.created_at', $rentang))
            ->groupBy('alats.id', 'alats.nama')
            ->orderByDesc('total_pinjam')
            ->limit(5)
            ->get();

        // B. Tren Peminjaman sesuai periode
        $trenHarian = $this->trenPeminjaman($filter);

        // 3. TABEL MONITORING (Recent & Alert)
        $recentActivities = Pinjam::with(['user', 'alat'])
            ->when($rentang, fn ($query) => $query->whereBetween('created_at', $rentang))
            ->latest()
            ->limit(5)
            ->get();

        $stockAlerts = Alat::where('jumlah_stok', '<=', 2)
            ->orWhere('kondisi_rusak', '>', 0)
            ->orWhere('kondisi_pengecekan', '>', 0)
            ->limit(5)
            ->get();

        return compact(
            'totalSKU', 'totalUnit', 'kondisiBaik', 'kondisiPengecekan', 'kondisiRusak', 'kondisiMtc',
            'stokTersedia', 'sedangDipinjam', 'pendingApproval', 'activeLoans', 'overdueCount',
            'topItems', 'trenHarian', 'recentActivities', 'stockAlerts'
        );
    }

    private function trenPeminjaman(array $filter)
    {
        // Default (semua data): tetap tampilkan 7 hari terakhir seperti sebelumnya.
        if ($filter['periode'] === 'semua') {
            $rows = Pinjam::select(DB::raw('DATE(created_at) as label'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();

            return $rows->map(function ($row) {
                return (object) [
                    'label' => Carbon::parse($row->label)->locale('id')->translatedFormat('d M'),
                    'total' => (int) $row->total,
                ];
            })->values();
        }

        if ($filter['group'] === 'month') {
            $rows = Pinjam::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"), DB::raw('count(*) as total'))
                ->whereBetween('created_at', [$filter['mulai'], $filter['selesai']])
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get()
                ->pluck('total', 'label');

            $data = collect();

            for ($bulan = $filter['mulai']->copy()->startOfMonth(); $bulan->lte($filter['selesai']); $bulan->addMonth()) {
                $key = $bulan->format('Y-m');

                $data->push((object) [
                    'label' => $bulan->locale('id')->translatedFormat('M Y'),
                    'total' => (int) ($rows[$key] ?? 0),
                ]);
            }

            return $data;
        }

        $rows = Pinjam::select(DB::raw('DATE(created_at) as label'), DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$filter['mulai'], $filter['selesai']])
            ->groupBy('label')
            ->orderBy('label', 'asc')
            ->get()
            ->pluck('total', 'label');

        $data = collect();

        for ($hari = $filter['mulai']->copy(); $hari->lte($filter['selesai']); $hari->addDay()) {
            $key = $hari->toDateString();

            $data->push((object) [
                'label' => $hari->locale('id')->translatedFormat('d M'),
                'total' => (int) ($rows[$key] ?? 0),
            ]);
        }

        return $data;
    }
}
