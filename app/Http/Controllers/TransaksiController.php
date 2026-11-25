<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Support\JadwalSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TransaksiController extends Controller
{
    /**
     * Tampilkan halaman bukti peminjaman / pengembalian dengan verifikasi QR per transaksi
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

        $jadwalSekolah = JadwalSekolah::ringkasan();

        return view('pinjam.bukti', compact('pinjamanAmbil', 'pinjamanKembali', 'jadwalSekolah'));
    }

    /**
     * Tampilkan scanner QR jika digunakan secara langsung
     */
    public function scanner()
    {
        return $this->create();
    }

    /**
     * API: Verifikasi QR Code transaksi (dibuat khusus per peminjaman).
     */
    public function cariKode(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:255',
            'aksi' => 'required|string|in:AMBIL,KEMBALI',
        ]);

        $kode = trim($request->kode);
        $aksi = strtoupper($request->aksi);

        // Format: SIJALAT-AMBIL-{id}-{token} / SIJALAT-KEMBALI-{id}-{token}
        if (! preg_match('/^SIJALAT-(AMBIL|KEMBALI)-(\d+)-([A-Za-z0-9]+)$/', $kode, $cocok)) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak dikenali. Pastikan Anda men-scan QR yang ditunjukkan guru.',
            ], 422);
        }

        $jenis = $cocok[1];
        $pinjamId = (int) $cocok[2];
        $token = $cocok[3];

        if ($jenis !== $aksi) {
            return response()->json([
                'success' => false,
                'message' => 'QR ini adalah QR ' . ($jenis === 'AMBIL' ? 'pengambilan' : 'pengembalian')
                    . ', bukan QR ' . ($aksi === 'AMBIL' ? 'pengambilan' : 'pengembalian') . '.',
            ], 422);
        }

        $pinjam = Pinjam::with('alat')
            ->where('id', $pinjamId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $pinjam) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code ini bukan milik peminjaman Anda.',
            ], 404);
        }

        $tokenTersimpan = $jenis === 'AMBIL' ? $pinjam->qr_ambil_token : $pinjam->qr_kembali_token;

        if (empty($tokenTersimpan) || ! hash_equals($tokenTersimpan, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid atau sudah kedaluwarsa. Minta guru untuk menampilkan ulang QR-nya.',
            ], 422);
        }

        $statusWajib = $jenis === 'AMBIL' ? 'disetuji' : 'dipinjam';

        if ($pinjam->status !== $statusWajib) {
            if ($jenis === 'AMBIL' && $pinjam->status === 'pending') {
                $pesan = 'Peminjaman ini belum disetujui guru, QR pengambilan belum bisa dipakai.';
            } elseif ($jenis === 'AMBIL') {
                $pesan = 'Alat untuk peminjaman ini sudah diambil sebelumnya.';
            } elseif ($pinjam->status === 'dikembalikan') {
                $pesan = 'Peminjaman ini sudah selesai dan alat sudah dikembalikan.';
            } else {
                $pesan = 'Status peminjaman tidak sesuai untuk pengembalian.';
            }

            return response()->json([
                'success' => false,
                'message' => $pesan,
            ], 409);
        }

        if ($pesanWaktu = $this->cekWaktuTransaksi($pinjam, $jenis)) {
            return response()->json([
                'success' => false,
                'message' => $pesanWaktu,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'tipe'    => 'QR_TRANSAKSI',
            'message' => 'QR ' . ($jenis === 'AMBIL' ? 'pengambilan' : 'pengembalian')
                . ' cocok dengan transaksi ' . ($pinjam->alat->nama ?? 'alat') . '.',
            'data'    => [
                'id'           => $pinjam->id,
                'jenis'        => strtolower($jenis),
                'kode_pinjam'  => $pinjam->kode_pinjam,
                'barang'       => $pinjam->alat->nama ?? '',
                'jumlah_minta' => $pinjam->jumlah_minta,
                'status'       => $pinjam->status,
                'token'        => $token,
                'nama'         => Auth::user()->name ?? '',
                'nis'          => Auth::user()->nis ?? '',
                'kelas'        => Auth::user()->kelas ?? '',
                'waktu_mulai'  => $pinjam->dipinjam_saat
                    ? \Carbon\Carbon::parse($pinjam->dipinjam_saat)->format('H:i')
                    : '',
                'waktu_selesai' => $pinjam->dikembalikan_saat
                    ? \Carbon\Carbon::parse($pinjam->dikembalikan_saat)->format('H:i')
                    : '',
                'tempat'       => $pinjam->ruangan ?? '',
                'jadwal'       => trim(
                    ($pinjam->dipinjam_saat
                        ? \Carbon\Carbon::parse($pinjam->dipinjam_saat)->format('d M Y, H.i')
                        : '')
                    . ' - ' .
                    ($pinjam->dikembalikan_saat
                        ? \Carbon\Carbon::parse($pinjam->dikembalikan_saat)->format('d M Y, H.i')
                        : '')
                ),
            ],
        ]);
    }

    /**
     * Proses transaksi pengambilan atau pengembalian alat.
     * Wajib menyertakan token QR yang sudah diverifikasi.
     */
    public function proses(Request $request)
    {
        $request->validate([
            'aksi'        => 'required|in:AMBIL,KEMBALI',
            'pinjam_id'   => 'required|exists:pinjams,id',
            'qr_token'    => 'required|string',
            'nis'         => 'required|string',
            'barang'      => 'required|string',
            'bukti_foto'  => 'required|image|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png|max:2048',
            'catatan'     => 'nullable|string|max:1000',
            'kondisi_kembali' => 'nullable|required_if:aksi,KEMBALI|in:baik,pengecekan,rusak',
            'catatan_kembali' => 'nullable|string|max:1000',
        ], [
            'pinjam_id.required' => 'Scan atau verifikasi QR dari guru dulu sebelum mengirim bukti.',
            'pinjam_id.exists'   => 'Data peminjaman tidak ditemukan. Scan ulang QR dari guru.',
            'qr_token.required'  => 'Token QR belum terisi. Scan ulang QR dari guru.',
            'barang.required'    => 'Data barang belum terisi. Verifikasi QR dari guru terlebih dahulu.',
            'bukti_foto.required' => 'Foto bukti wajib diunggah.',
            'bukti_foto.image'    => 'File bukti harus berupa gambar.',
            'bukti_foto.mimes'    => 'Foto bukti harus berformat JPG atau PNG.',
            'bukti_foto.max'      => 'Ukuran foto bukti maksimal 2 MB. Kecilkan foto atau pilih foto lain.',
            'bukti_foto.uploaded' => 'Foto gagal diunggah karena melebihi batas server. Coba foto yang lebih kecil.',
        ]);

        $aksi = $request->aksi;

        $pinjam = Pinjam::with(['user', 'alat'])
            ->where('id', $request->pinjam_id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $pinjam) {
            return back()->with('error', 'Data peminjaman tidak ditemukan atau bukan milik Anda.')->withInput();
        }

        // Pastikan transaksi ini benar-benar sudah diverifikasi lewat QR yang sesuai
        $tokenTersimpan = $aksi === 'AMBIL' ? $pinjam->qr_ambil_token : $pinjam->qr_kembali_token;

        if (empty($tokenTersimpan) || ! hash_equals($tokenTersimpan, (string) $request->qr_token)) {
            return back()->with('error', 'Verifikasi QR tidak valid. Scan ulang QR yang ditunjukkan guru.')->withInput();
        }

        $statusWajib = $aksi === 'AMBIL' ? 'disetuji' : 'dipinjam';

        if ($pinjam->status !== $statusWajib) {
            return back()->with('error', 'Status peminjaman sudah berubah. Silakan muat ulang halaman.')->withInput();
        }

        if ($pesanWaktu = $this->cekWaktuTransaksi($pinjam, $aksi)) {
            return back()->with('error', $pesanWaktu)->withInput();
        }

        // Bukti foto disimpan di disk privat (storage/app/private) agar tidak
        // bisa diakses langsung lewat /storage. Admin membukanya lewat route
        // BuktiController yang sudah dilindungi middleware admin.
        $pathFoto = $request->file('bukti_foto')->store('bukti_transaksi', 'local');
        $pesanError = null;

        try {
            DB::transaction(function () use ($aksi, $request, $pinjam, $pathFoto, $statusWajib, &$pesanError) {
                // Kunci baris peminjaman dan alat agar dua scan yang masuk
                // bersamaan tidak bisa memproses transaksi yang sama dua kali
                // (mencegah stok berkurang/bertambah ganda).
                $terkunci = Pinjam::whereKey($pinjam->id)->lockForUpdate()->first();
                $alat = $terkunci && $terkunci->alat_id
                    ? Alat::whereKey($terkunci->alat_id)->lockForUpdate()->first()
                    : null;

                if (! $terkunci || $terkunci->status !== $statusWajib) {
                    $pesanError = 'Status peminjaman sudah berubah. Silakan muat ulang halaman.';

                    throw new RuntimeException('transaksi-dibatalkan');
                }

                if ($aksi === 'AMBIL') {
                    // Jangan izinkan pengambilan jika stok fisik sudah tidak mencukupi.
                    if (! $alat || $alat->jumlah_stok < $terkunci->jumlah_minta) {
                        $pesanError = 'Stok alat tidak mencukupi untuk pengambilan ini. Hubungi guru atau admin.';

                        throw new RuntimeException('transaksi-dibatalkan');
                    }

                    $terkunci->status      = 'dipinjam';
                    $terkunci->bukti_ambil = $pathFoto;

                    if ($request->filled('catatan')) {
                        $terkunci->catatan = $request->catatan;
                    }

                    $terkunci->save();

                    $alat->decrement('jumlah_stok', $terkunci->jumlah_minta);

                    return;
                }

                $kondisi = $request->input('kondisi_kembali', 'baik');
                $jumlah = (int) $terkunci->jumlah_minta;

                $terkunci->status              = 'dikembalikan';
                $terkunci->bukti_kembali       = $pathFoto;
                $terkunci->kondisi_kembali     = $kondisi;
                $terkunci->dikembalikan_aktual = now();

                if ($request->filled('catatan_kembali')) {
                    $terkunci->catatan_kembali = $request->catatan_kembali;
                }

                $terkunci->save();

                if ($alat) {
                    $alat->increment('jumlah_stok', $jumlah);

                    // Pindahkan qty dari kondisi baik ke kondisi yang dilaporkan siswa.
                    // Jika data lama tidak konsisten (kondisi_baik lebih kecil dari qty),
                    // pindahkan sebanyak yang tersedia saja agar total unit fisik tidak
                    // bertambah dan stok tidak melebihi jumlah unit sebenarnya.
                    $pindah = min($jumlah, max(0, (int) $alat->kondisi_baik));

                    if ($kondisi === 'pengecekan') {
                        $alat->kondisi_baik = (int) $alat->kondisi_baik - $pindah;
                        $alat->kondisi_pengecekan = (int) $alat->kondisi_pengecekan + $pindah;
                    } elseif ($kondisi === 'rusak') {
                        $alat->kondisi_baik = (int) $alat->kondisi_baik - $pindah;
                        $alat->kondisi_rusak = (int) $alat->kondisi_rusak + $pindah;
                    }

                    $alat->save();
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($pathFoto);

            if ($pesanError !== null) {
                return back()->with('error', $pesanError)->withInput();
            }

            throw $e;
        }

        $pesan = ($aksi === 'AMBIL') ? 'Pengambilan alat berhasil diverifikasi & dicatat!' : 'Pengembalian alat berhasil diselesaikan!';
        return redirect()->route('dashboard')->with('success', $pesan)->with('struk_id', $pinjam->id);
    }

    /**
     * Validasi waktu pengambilan/pengembalian terhadap jadwal sekolah.
     * Mengembalikan pesan error, atau null jika boleh diproses.
     */
    private function cekWaktuTransaksi(Pinjam $pinjam, string $aksi): ?string
    {
        $sekarang = now()->timezone('Asia/Jakarta');

        if ($aksi === 'AMBIL') {
            if (! $pinjam->dipinjam_saat) {
                return 'Jadwal pengambilan belum diisi. Hubungi guru atau admin.';
            }

            $jadwal = Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta');

            if (! JadwalSekolah::hariSekolah($jadwal)) {
                return 'Pengambilan hanya dapat dilakukan pada hari Senin sampai Jumat.';
            }

            $tanggal = $jadwal->locale('id')->translatedFormat('d F Y');

            if ($sekarang->toDateString() !== $jadwal->toDateString()) {
                return 'Pengambilan hanya dapat dilakukan pada tanggal ' . $tanggal
                    . ' sesuai form peminjaman.';
            }

            $mulai = JadwalSekolah::ambilMulai($jadwal);

            if ($sekarang->lt($mulai)) {
                return 'Pengambilan paling cepat pukul ' . $mulai->format('H.i')
                    . ' pada tanggal ' . $tanggal . '.';
            }

            $batas = JadwalSekolah::kembaliTerakhir($jadwal);

            if ($batas && $sekarang->gt($batas)) {
                return 'Pengambilan sudah melewati jam terakhir pembelajaran (pukul '
                    . $batas->format('H.i') . ').';
            }

            return null;
        }

        $batasHariIni = JadwalSekolah::kembaliTerakhir($sekarang);

        if (! $batasHariIni) {
            return 'Pengembalian hanya dapat dilakukan pada hari Senin sampai Jumat.';
        }

        if ($sekarang->gt($batasHariIni)) {
            return 'Pengembalian paling lambat pukul ' . $batasHariIni->format('H.i')
                . ' (jam terakhir pembelajaran). Silakan kembalikan pada hari sekolah berikutnya.';
        }

        return null;
    }
}
