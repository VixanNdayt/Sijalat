<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransaksiController extends Controller
{
    /**
     * Tampilkan halaman bukti peminjaman / pengembalian dengan verifikasi QR meja lab
     */
    public function create()
    {
        $user = Auth::user();

        // Peminjaman milik siswa yang siap diambil (status: disetuji atau pending)
        $pinjamanAmbil = Pinjam::with('alat')
            ->where('user_id', $user->id)
            ->whereIn('status', ['disetuji'])
            ->latest()
            ->get();

        // Peminjaman milik siswa yang siap dikembalikan (status: dipinjam)
        $pinjamanKembali = Pinjam::with('alat')
            ->where('user_id', $user->id)
            ->where('status', 'dipinjam')
            ->latest()
            ->get();

        return view('pinjam.bukti', compact('pinjamanAmbil', 'pinjamanKembali'));
    }

    /**
     * Tampilkan scanner QR jika digunakan secara langsung
     */
    public function scanner()
    {
        return $this->create();
    }

    /**
     * API: Verifikasi QR Code Meja Lab atau Kode Peminjaman
     */
    public function cariKode(Request $request)
    {
        $request->validate([
            'kode' => 'required|string',
            'aksi' => 'required|string|in:AMBIL,KEMBALI',
        ]);

        $kode = trim($request->kode);
        $aksi = $request->aksi;

        // Kode QR Meja Lab yang sah
        $kodeResmiMeja = ($aksi === 'AMBIL') ? 'SIJALAT-AMBIL-LAB' : 'SIJALAT-KEMBALI-LAB';

        // 1. Cek apakah ini QR Code Meja Lab
        if (strtoupper($kode) === $kodeResmiMeja) {
            $user = Auth::user();
            
            // Ambil peminjaman aktif user yang sesuai aksi
            $pinjam = Pinjam::with('alat')
                ->where('user_id', $user->id)
                ->when($aksi === 'AMBIL', function ($q) {
                    $q->whereIn('status', ['disetuji']);
                }, function ($q) {
                    $q->where('status', 'dipinjam');
                })
                ->latest()
                ->first();

            return response()->json([
                'success'       => true,
                'tipe'          => 'MEJA_LAB',
                'message'       => 'Verifikasi Meja Lab Berhasil! Lokasi terkonfirmasi.',
                'pinjam_aktif'  => $pinjam ? [
                    'id'          => $pinjam->id,
                    'kode_pinjam' => $pinjam->kode_pinjam,
                    'barang'      => $pinjam->alat->nama ?? '',
                    'status'      => $pinjam->status,
                ] : null,
            ]);
        }

        // 2. Jika bukan QR Meja, cek apakah ini kode_pinjam perorangan (dukungan ganda)
        $pinjam = Pinjam::with(['user', 'alat'])
                    ->where('kode_pinjam', $kode)
                    ->first();

        if ($pinjam) {
            return response()->json([
                'success' => true,
                'tipe'    => 'KODE_PINJAM',
                'message' => 'Kode peminjaman terverifikasi.',
                'data'    => [
                    'id'          => $pinjam->id,
                    'kode_pinjam' => $pinjam->kode_pinjam,
                    'nama'        => $pinjam->user->name ?? '',
                    'nis'         => $pinjam->user->nis ?? '',
                    'barang'      => $pinjam->alat->nama ?? '',
                    'status'      => $pinjam->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'QR Code tidak valid! Pastikan Anda men-scan QR Code yang tertempel di Meja Lab ' . ($aksi === 'AMBIL' ? 'Pengambilan' : 'Pengembalian') . '.',
        ], 404);
    }

    /**
     * Proses transaksi pengambilan atau pengembalian alat
     */
    public function proses(Request $request)
    {
        $request->validate([
            'aksi'        => 'required|in:AMBIL,KEMBALI',
            'pinjam_id'   => 'nullable|exists:pinjams,id',
            'kode_pinjam' => 'nullable|string',
            'nis'         => 'required|string',
            'barang'      => 'required|string',
            'bukti_foto'  => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'catatan'     => 'nullable|string',
        ]);

        $aksi = $request->aksi;
        $pinjam = null;

        // Cari berdasarkan pinjam_id yang dipilih di dropdown
        if ($request->filled('pinjam_id')) {
            $pinjam = Pinjam::with(['user', 'alat'])->find($request->pinjam_id);
        }

        // Jika tidak ada pinjam_id, cari berdasarkan kode_pinjam
        if (!$pinjam && $request->filled('kode_pinjam')) {
            $pinjam = Pinjam::with(['user', 'alat'])
                        ->where('kode_pinjam', trim($request->kode_pinjam))
                        ->first();
        }

        // Fallback: Cari berdasarkan NIS & nama barang
        if (!$pinjam) {
            $user = User::where('nis', $request->nis)->first();
            if (!$user) {
                return back()->with('error', 'Siswa dengan NIS tersebut tidak ditemukan!')->withInput();
            }

            $alat = Alat::where('nama', 'LIKE', '%' . $request->barang . '%')->first();
            if (!$alat) {
                return back()->with('error', 'Data alat tidak ditemukan di katalog!')->withInput();
            }

            $pinjam = Pinjam::where('user_id', $user->id)
                            ->where('alat_id', $alat->id)
                            ->when($aksi === 'AMBIL', function ($q) {
                                $q->whereIn('status', ['disetuji']);
                            }, function ($q) {
                                $q->where('status', 'dipinjam');
                            })
                            ->latest()
                            ->first();
        }

        if (!$pinjam) {
            return back()->with('error', "Tidak ada data peminjaman aktif untuk aksi $aksi pada siswa/alat ini.")->withInput();
        }

        // Upload Gambar Bukti
        $pathFoto = $request->file('bukti_foto')->store('bukti_transaksi', 'public');

        // Perubahan Status dan Stok Alat
        $alat = $pinjam->alat;

        if ($aksi === 'AMBIL') {
            $pinjam->status      = 'dipinjam';
            $pinjam->bukti_ambil = $pathFoto;
            if ($alat && $alat->jumlah_stok >= $pinjam->jumlah_minta) {
                $alat->decrement('jumlah_stok', $pinjam->jumlah_minta);
            }
        } else {
            $pinjam->status        = 'dikembalikan';
            $pinjam->bukti_kembali = $pathFoto;
            if ($alat) {
                $alat->increment('jumlah_stok', $pinjam->jumlah_minta);
            }
        }

        if ($request->filled('catatan')) {
            $pinjam->catatan = $request->catatan;
        }

        $pinjam->save();

        $pesan = ($aksi === 'AMBIL') ? 'Pengambilan alat berhasil diverifikasi & dicatat!' : 'Pengembalian alat berhasil diselesaikan!';
        return redirect()->route('dashboard')->with('success', $pesan);
    }
}
