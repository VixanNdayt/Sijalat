<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Pinjam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Pinjam::query();

        if ($user->role_user !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($user->role_user === 'admin') {
            // Ringkasan hanya menghitung data satu bulan terakhir, sesuai banner informasi.
            $sejak = now()->subMonth();
            $bulanIni = Pinjam::where('created_at', '>=', $sejak);

            $totalMasuk = (clone $bulanIni)->count();
            $totalApproved = (clone $bulanIni)
                ->whereIn('status', ['disetuji', 'dipinjam', 'dikembalikan'])
                ->count();
            $totalPending = (clone $bulanIni)->where('status', 'pending')->count();
            $totalCatatan = (clone $bulanIni)->terlambat()->count();

            // Tabel riwayat: 10 transaksi terbaru pada periode yang sama.
            $transaksis = Pinjam::with(['user', 'alat'])
                ->where('created_at', '>=', $sejak)
                ->orderByDesc('created_at')
                ->take(10)
                ->get();

            // Grafik: 5 alat yang benar-benar pernah dipinjam (bukan sekadar diajukan).
            $chartQuery = DB::table('pinjams')
                ->join('alats', 'pinjams.alat_id', '=', 'alats.id')
                ->whereIn('pinjams.status', ['dipinjam', 'dikembalikan'])
                ->where('pinjams.created_at', '>=', $sejak)
                ->select('alats.nama', DB::raw('COUNT(pinjams.id) as total_pinjam'))
                ->groupBy('alats.id', 'alats.nama')
                ->orderByDesc('total_pinjam')
                ->limit(5)
                ->get();

            $chartLabels = $chartQuery->pluck('nama');
            $chartData = $chartQuery->pluck('total_pinjam');

            return view('admin.dashboard', compact(
                'totalMasuk',
                'totalApproved',
                'totalPending',
                'totalCatatan',
                'transaksis',
                'chartLabels',
                'chartData'
            ));
        }

        $permintaanTerbaru = (clone $query)->where('status', 'pending')->latest()->first();
        $pinjamanAktifTerbaru = (clone $query)->where('status', 'dipinjam')->latest()->first();

        $menunggu = (clone $query)->where('status', 'pending')->count();
        $sedangDipinjam = (clone $query)->where('status', 'dipinjam')->count();
        $dikembalikan = (clone $query)->where('status', 'dikembalikan')->count();

        $pinjamanAktif = (clone $query)
            ->with('alat')
            ->whereIn('status', ['pending', 'dipinjam'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pelanggaran = (clone $query)->terlambat()->count();

        $daftarPelanggaran = (clone $query)
            ->with('alat')
            ->terlambat()
            ->latest()
            ->take(5)
            ->get();

        // Batasi nomor halaman agar tidak melewati halaman terakhir.
        $perHalaman = 5;
        $totalRiwayat = (clone $query)->count();
        $halamanTerakhir = max(1, (int) ceil($totalRiwayat / $perHalaman));
        $halaman = min(max(1, (int) request('page', 1)), $halamanTerakhir);

        $riwayat = $query->with('alat')->latest()->paginate($perHalaman, ['*'], 'page', $halaman);

        $unreadChat = Message::unreadCountFor($user);

        return view('siswa.dashboard', compact(
            'menunggu',
            'sedangDipinjam',
            'dikembalikan',
            'pelanggaran',
            'riwayat',
            'pinjamanAktif',
            'permintaanTerbaru',
            'pinjamanAktifTerbaru',
            'daftarPelanggaran',
            'unreadChat'
        ));
    }
}
