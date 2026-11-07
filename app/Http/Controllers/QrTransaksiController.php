<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrTransaksiController extends Controller
{
    /**
     * Pastikan hanya admin/guru yang bisa mengakses halaman QR.
     */
    private function pastikanAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403, 'Halaman ini hanya untuk guru/admin.');
    }

    /**
     * Daftar transaksi yang QR-nya bisa ditampilkan ke siswa.
     */
    public function index(Request $request)
    {
        $this->pastikanAdmin();

        $cari = $request->query('cari');
        $cari = is_string($cari) ? trim($cari) : '';

        $pinjams = Pinjam::with(['user', 'alat'])
            ->whereIn('status', ['disetuji', 'dipinjam'])
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($q) use ($cari) {
                    $q->where('kode_pinjam', 'like', '%' . $cari . '%')
                        ->orWhereHas('user', function ($u) use ($cari) {
                            $u->where('name', 'like', '%' . $cari . '%')
                                ->orWhere('nis', 'like', '%' . $cari . '%');
                        })
                        ->orWhereHas('alat', function ($a) use ($cari) {
                            $a->where('nama', 'like', '%' . $cari . '%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('admin.qr', compact('pinjams', 'cari'));
    }

    /**
     * Halaman QR siap cetak / ditampilkan ke siswa.
     */
    public function print(Pinjam $pinjam, string $jenis)
    {
        $this->pastikanAdmin();

        $jenis = strtolower($jenis);

        abort_unless(in_array($jenis, ['ambil', 'kembali'], true), 404);

        // QR hanya boleh dibuat saat transisinya memang valid, agar tidak
        // menghasilkan QR yang pasti ditolak saat dipindai.
        $statusWajib = $jenis === 'ambil' ? 'disetuji' : 'dipinjam';

        abort_unless(
            $pinjam->status === $statusWajib,
            409,
            'Status peminjaman tidak sesuai untuk menampilkan QR ' . $jenis . '.'
        );

        // Pastikan token tersedia (data lama)
        $pinjam->pastikanTokenQr();
        $pinjam->load(['user', 'alat']);

        return view('admin.qr-print', [
            'pinjam' => $pinjam,
            'jenis'  => $jenis,
            'kode'   => $pinjam->kodeQr($jenis),
        ]);
    }
}
