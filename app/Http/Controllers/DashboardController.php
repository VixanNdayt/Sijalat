<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        $user = Auth::user();
        $query = Pinjam::with('alat');

        if ($user->role_user !== 'admin') {
        $query->where('user_id', $user->id);
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

    $riwayat = $query->latest()->paginate(5);

    return view('dashboard', compact('menunggu', 'sedangDipinjam', 'dikembalikan', 'pelanggaran','riwayat', 'pinjamanAktif', 'terbaru'));
    }
}
