<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransaksiController extends Controller
{
    /**
     * Tampilkan halaman bukti peminjaman / pengembalian
     */
    public function create()
    {
        return view('pinjam.bukti');
    }

    /**
     * Tampilkan scanner QR jika digunakan
     */
    public function scanner()
    {
        return view('pinjam.bukti');
    }

    /**
     * Proses transaksi pengambilan atau pengembalian alat
     */
    public function proses(Request $request)
    {
        // 1. Validasi Input berdasarkan form manual HTML
        $request->validate([
            'aksi'       => 'required|in:AMBIL,KEMBALI',
            'nis'        => 'required|string',
            'barang'     => 'required|string',
            'bukti_foto' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'catatan'    => 'nullable|string',
        ]);

        $aksi = $request->aksi;

        // 2. Cari User (Siswa) berdasarkan NIS dari input form
        $user = User::where('nis', $request->nis)->first();
        if (!$user) {
            return back()->with('error', 'Siswa dengan NIS tersebut tidak ditemukan!')->withInput();
        }

        // 3. Cari Alat berdasarkan teks yang diketik di input "Barang"
        $alat = Alat::where('nama', 'LIKE', '%' . $request->barang . '%')->first();
        if (!$alat) {
            return back()->with('error', 'Data alat tidak ditemukan di katalog!')->withInput();
        }

        // 4. Cari Transaksi Peminjaman berdasarkan User, Alat, dan Status
        // Jika AMBIL: cari yang disetujui atau pending
        // Jika KEMBALI: cari yang sedang dipinjam
        $pinjam = Pinjam::where('user_id', $user->id)
                        ->where('alat_id', $alat->id)
                        ->when($aksi === 'AMBIL', function ($q) {
                            $q->whereIn('status', ['disetuji', 'pending']);
                        }, function ($q) {
                            $q->where('status', 'dipinjam');
                        })
                        ->latest()
                        ->first();

        if (!$pinjam) {
            return back()->with('error', "Tidak ada data peminjaman valid untuk aksi $aksi pada alat ini.")->withInput();
        }

        // 5. Proses Upload Gambar
        $pathFoto = $request->file('bukti_foto')->store('bukti_transaksi', 'public');

        // 6. Eksekusi Perubahan Status dan Stok (menggunakan kolom jumlah_stok)
        if ($aksi === 'AMBIL') {
            $pinjam->status      = 'dipinjam';
            $pinjam->bukti_ambil = $pathFoto;
            if ($alat->jumlah_stok >= $pinjam->jumlah_minta) {
                $alat->decrement('jumlah_stok', $pinjam->jumlah_minta);
            }
        } else {
            $pinjam->status        = 'dikembalikan';
            $pinjam->bukti_kembali = $pathFoto;
            $alat->increment('jumlah_stok', $pinjam->jumlah_minta);
        }

        // Simpan catatan dari textarea
        $pinjam->catatan = $request->catatan;
        $pinjam->save();

        return redirect()->route('dashboard')->with('success', "Proses $aksi berhasil diselesaikan!");
    }
}
