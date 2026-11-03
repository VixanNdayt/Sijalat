<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index() {
        $user = Auth::user();
        $query = Pinjam::with('alat');

        if ($user->role_user !== 'admin') {
        $query->where('user_id', $user->id);
    }

    if (Auth::user()->role_user === 'admin') {

        $totalMasuk = Pinjam::count();
        $totalApproved = Pinjam::where('status', 'disetuji')->count();
        $totalPending = Pinjam::where('status', 'pending')->count();
        $totalCatatan = Pinjam::whereNotNull('catatan')->count();

            // Siapkan data untuk tabel riwayat (ambil 10 terbaru)[cite: 7]
       $transaksis = Pinjam::with(['user', 'alat'])->orderBy('created_at', 'desc')->take(10)->get();

        // QUERY UNTUK CHART (Mencari 5 barang paling sering dipinjam)
        $chartQuery = DB::table('pinjams')
            ->join('alats', 'pinjams.alat_id', '=', 'alats.id')
            // Menggunakan COUNT untuk menghitung seberapa sering transaksi terjadi
            // Jika ingin menghitung total jumlah (qty) barang, ganti menjadi SUM(pinjams.jumlah_minta)
            ->select('alats.nama', DB::raw('COUNT(pinjams.id) as total_pinjam')) 
            ->groupBy('alats.id', 'alats.nama')
            ->orderByDesc('total_pinjam')
            ->limit(5)
            ->get();
        // Memisahkan nama barang (labels) dan angka jumlahnya (data) menjadi dua array
        $chartLabels = $chartQuery->pluck('nama');
        $chartData = $chartQuery->pluck('total_pinjam');
        // Tambahkan $chartLabels dan $chartData ke dalam compact()
        return view('admin.dashboard', compact(
            'totalMasuk', 'totalApproved', 'totalPending', 'totalCatatan', 
            'transaksis', 'chartLabels', 'chartData'
        ));
    }

    $terbaru = (clone $query)->where('status', 'pending')->latest()->first();

    $menunggu = (clone $query)->where('status', 'pending')->count();
    $sedangDipinjam = (clone $query)->where('status', 'dipinjam')->count();
    $dikembalikan = (clone $query)->where('status', 'dikembalikan')->count();

    $pinjamanAktif = (clone $query)
        ->whereIn('status', ['pending', 'dipinjam'])
        ->orderBy('created_at', 'desc')
        ->get();

    $pelanggaran = (clone $query)->whereIn('status', ['terlambat', 'ditolak'])->count();

    $daftarPelanggaran = (clone $query)
        ->whereIn('status', ['terlambat', 'ditolak'])
        ->latest()
        ->take(5)
        ->get();

    $riwayat = $query->latest()->paginate(5);
    // Jika yang login adalah Siswa, arahkan ke dashboard siswa seperti biasa
    return view('siswa.dashboard', compact('menunggu', 'sedangDipinjam', 'dikembalikan', 'pelanggaran','riwayat', 'pinjamanAktif', 'terbaru', 'daftarPelanggaran'));
    }
}
