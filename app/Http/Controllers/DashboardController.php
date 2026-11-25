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
            $transaksis = Pinjam::with(['user', 'alat', 'guru'])
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

    /**
     * Data lengkap satu transaksi untuk modal detail di dashboard admin.
     */
    public function detail(Pinjam $pinjam)
    {
        $pinjam->load(['user', 'alat', 'guru', 'mapel']);

        $waktu = fn ($nilai) => $nilai
            ? \Carbon\Carbon::parse($nilai)
                ->timezone('Asia/Jakarta')
                ->locale('id')
                ->translatedFormat('d F Y, H.i')
            : null;

        return response()->json([
            'id' => $pinjam->id,
            'kode_pinjam' => $pinjam->kode_pinjam,
            'status_label' => $pinjam->status_label,
            'status_tone' => $pinjam->status_tone,
            'qr_url' => in_array($pinjam->status, ['disetuji', 'dipinjam'], true)
                ? route('admin.qr.index', ['cari' => $pinjam->kode_pinjam])
                : null,
            'siswa' => [
                'nama' => $pinjam->user?->name ?? '-',
                'nis' => filled($pinjam->user?->nis) ? (string) $pinjam->user->nis : '-',
                'kelas' => filled($pinjam->user?->kelas) ? (string) $pinjam->user->kelas : '-',
                'email' => $pinjam->user?->email ?? '-',
            ],
            'alat' => [
                'nama' => $pinjam->alat?->nama ?? '-',
                'kode' => $pinjam->alat?->code ?? '-',
                'lokasi' => $pinjam->alat?->lokasi ?? '-',
                'guru' => $pinjam->guru?->nama ?? '-',
                'mapel' => $pinjam->mapel?->nama_mapel ?? '-',
                'jumlah' => (int) $pinjam->jumlah_minta . ' unit',
            ],
            'waktu' => [
                'diajukan' => $waktu($pinjam->created_at),
                'mulai_pinjam' => $waktu($pinjam->dipinjam_saat),
                'tenggat_kembali' => $waktu($pinjam->dikembalikan_saat),
                'dikembalikan' => $waktu($pinjam->dikembalikan_aktual),
                'kondisi_kembali' => $pinjam->kondisi_kembali_label,
                'keterlambatan' => $pinjam->terlambat_label,
            ],
            'keterangan' => [
                'keperluan' => $pinjam->deskripsi_minta ?: '-',
                'ruangan' => $pinjam->ruangan ?: '-',
                'catatan' => $pinjam->catatan ?: '-',
                'catatan_kembali' => $pinjam->catatan_kembali ?: '-',
            ],
            'bukti' => [
                'ambil' => $pinjam->bukti_ambil
                    ? route('admin.bukti.foto', [$pinjam->id, 'ambil'])
                    : null,
                'kembali' => $pinjam->bukti_kembali
                    ? route('admin.bukti.foto', [$pinjam->id, 'kembali'])
                    : null,
            ],
        ]);
    }
}
