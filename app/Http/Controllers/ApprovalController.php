<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index()
    {
        $pendingList = Pinjam::with(['user', 'alat'])
                             ->where('status', 'pending')
                             ->orderBy('created_at', 'asc')
                             ->get();

        return view('admin.approval', compact('pendingList'));
    }

    public function proses(Request $request, $id)
    {
        $request->validate([
            'aksi' => 'required|in:setuju,tolak'
        ]);

        $pinjam = Pinjam::findOrFail($id);

        if ($request->aksi === 'setuju') {
            $pinjam->status = 'disetuji';
            $pesan = 'Peminjaman berhasil disetujui! Siswa sekarang bisa mengambil barang fisik.';
            
            // Catatan: Stok alat TIDAK dikurangi di sini. 
            // Stok baru berkurang saat siswa benar-benar scan/ambil barang secara fisik di TransaksiController.

        } elseif ($request->aksi === 'tolak') {
            $pinjam->status = 'ditolak';
            $pesan = 'Peminjaman telah ditolak.';
        }

        $pinjam->save();

        return back()->with('success');
    }
}
