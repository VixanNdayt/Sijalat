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

        if ($pinjam->status !== 'pending') {
            return back()->withErrors([
                'approval' => 'Pengajuan ini sudah diproses sebelumnya. Muat ulang halaman untuk melihat status terbaru.',
            ]);
        }

        if ($request->aksi === 'setuju') {
            $pinjam->status = 'disetuji';
            $pesan = 'Peminjaman disetujui! Buka halaman QR Transaksi untuk menampilkan QR pengambilan ke siswa.';
            
            // Catatan: Stok alat TIDAK dikurangi di sini. 
            // Stok baru berkurang saat siswa benar-benar scan/ambil barang secara fisik di TransaksiController.

        } elseif ($request->aksi === 'tolak') {
            $pinjam->status = 'ditolak';
            $pesan = 'Peminjaman telah ditolak.';
        }

        $pinjam->save();

        return back()->with('success', $pesan);
    }
}
