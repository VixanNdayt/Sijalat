<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use App\Support\RiwayatPeminjamanExport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RiwayatPeminjamanController extends Controller
{
    /**
     * Kolom transaksi yang boleh ikut diekspor. Kolom foto bukti
     * (bukti_ambil, bukti_kembali) sengaja tidak pernah diambil.
     */
    private const KOLOM = [
        'id',
        'kode_pinjam',
        'user_id',
        'alat_id',
        'guru_id',
        'mapel_id',
        'dipinjam_saat',
        'dikembalikan_saat',
        'dikembalikan_aktual',
        'jumlah_minta',
        'deskripsi_minta',
        'ruangan',
        'catatan',
        'kondisi_kembali',
        'catatan_kembali',
        'status',
        'created_at',
    ];

    private const MAKS_EKSPOR = 10000;

    public function index(Request $request)
    {
        $filter = $this->filter($request);

        $pinjams = $this->query($filter)
            ->paginate(20)
            ->withQueryString();

        $ringkasan = $this->ringkasan($filter);

        return view('admin.riwayat', compact('pinjams', 'filter', 'ringkasan'));
    }

    public function exportPdf(Request $request)
    {
        $filter = $this->filter($request);
        $pinjams = $this->query($filter)->limit(self::MAKS_EKSPOR)->get();

        $pdf = RiwayatPeminjamanExport::pdf($pinjams, $filter);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->namaFile($filter) . '.pdf"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filter = $this->filter($request);
        $pinjams = $this->query($filter)->limit(self::MAKS_EKSPOR)->get();

        $excel = RiwayatPeminjamanExport::excel($pinjams, $filter);

        return response($excel, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $this->namaFile($filter) . '.xlsx"',
            'Content-Length' => (string) strlen($excel),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function query(array $filter): Builder
    {
        return Pinjam::query()
            ->select(self::KOLOM)
            ->with([
                'user:id,name,nis,kelas',
                'alat:id,nama,code,jenis',
                'guru:id,nama',
                'mapel:id,nama_mapel',
            ])
            ->whereBetween('created_at', [$filter['mulai'], $filter['selesai']])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{0: int, 1: int, 2: array<string, int>, 3: int}
     */
    private function ringkasan(array $filter): array
    {
        $base = fn () => Pinjam::query()
            ->whereBetween('created_at', [$filter['mulai'], $filter['selesai']]);

        $status = $base()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        return [
            'total' => (int) $base()->count(),
            'unit' => (int) $base()->sum('jumlah_minta'),
            'status' => $status,
            'terlambat' => (int) $base()->terlambat()->count(),
        ];
    }

    /**
     * @return array{mulai: Carbon, selesai: Carbon, label: string}
     */
    private function filter(Request $request): array
    {
        $data = $request->validate([
            'mulai' => ['nullable', 'date_format:Y-m-d'],
            'selesai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $mulai = isset($data['mulai'])
            ? Carbon::createFromFormat('Y-m-d', $data['mulai'])->startOfDay()
            : Carbon::today()->startOfMonth()->startOfDay();

        $selesai = isset($data['selesai'])
            ? Carbon::createFromFormat('Y-m-d', $data['selesai'])->endOfDay()
            : Carbon::today()->endOfDay();

        if ($mulai->greaterThan($selesai)) {
            [$mulai, $selesai] = [$selesai->copy()->startOfDay(), $mulai->copy()->endOfDay()];
        }

        $label = $mulai->locale('id')->translatedFormat('d F Y')
            . ' s.d. ' . $selesai->locale('id')->translatedFormat('d F Y');

        return compact('mulai', 'selesai', 'label');
    }

    private function namaFile(array $filter): string
    {
        return 'Riwayat-Peminjaman-'
            . $filter['mulai']->format('Y-m-d')
            . '_sd_'
            . $filter['selesai']->format('Y-m-d');
    }
}
