<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        // 1. METRIC CARDS (Total & Kondisi)
        $totalSKU = Alat::count(); // Jenis barang
        $totalUnit = Alat::sum('jumlah_stok');
        
        $kondisiBaik = Alat::sum('kondisi_baik');
        $kondisiRusak = Alat::sum('kondisi_rusak');
        $kondisiMtc = Alat::sum('kondisi_maintenance');

        // Status Peminjaman
        $stokTersedia = $kondisiBaik; // Asumsi yang ready adalah yang baik dan tidak dipinjam
        $sedangDipinjam = Pinjam::where('status', 'dipinjam')->sum('jumlah_minta');
        
        $pendingApproval = Pinjam::where('status', 'pending')->count();
        $activeLoans = Pinjam::where('status', 'dipinjam')->count();
        
        // Overdue (Asumsi kolom tenggat waktu bernama 'waktu_selesai')
        $overdueCount = Pinjam::where('status', 'dipinjam')
                              ->where('created_at', '<', Carbon::today()) // Sesuaikan dengan logikamu
                              ->count();

        // 2. GRAFIK DATA
        // A. Top 5 Barang Paling Sering Dipinjam
        $topItems = DB::table('pinjams')
            ->join('alats', 'pinjams.alat_id', '=', 'alats.id')
            ->select('alats.nama', DB::raw('COUNT(pinjams.id) as total_pinjam'))
            ->groupBy('alats.id', 'alats.nama')
            ->orderByDesc('total_pinjam')
            ->limit(5)
            ->get();
            
        // B. Tren Peminjaman 7 Hari Terakhir (Line Chart)
        $trenHarian = Pinjam::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 3. TABEL MONITORING (Recent & Alert)
        $recentActivities = Pinjam::with(['user', 'alat'])->latest()->limit(5)->get();
        $stockAlerts = Alat::where('jumlah_stok', '<=', 2)->orWhere('kondisi_rusak', '>', 0)->limit(5)->get();

        return view('admin.analisis', compact(
            'totalSKU', 'totalUnit', 'kondisiBaik', 'kondisiRusak', 'kondisiMtc',
            'stokTersedia', 'sedangDipinjam', 'pendingApproval', 'activeLoans', 'overdueCount',
            'topItems', 'trenHarian', 'recentActivities', 'stockAlerts'
        ));
    }
}