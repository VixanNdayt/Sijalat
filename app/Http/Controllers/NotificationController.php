<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Pinjam;
use App\Models\User;
use App\Support\JadwalSekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * Notifikasi dikirim paling lambat 60 menit sebelum tenggat kembali.
     */
    private const MENIT_PENGINGAT_KEMBALI = 60;

    /**
     * Notifikasi berbeda untuk admin/guru dan siswa.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return $user->isAdmin()
            ? $this->untukAdmin($user)
            : $this->untukSiswa($user);
    }

    /**
     * Notifikasi admin: jadwal pengambilan/pengembalian hari ini, pengajuan
     * pending, chat belum dibaca, dan peminjaman yang melewati tenggat.
     */
    private function untukAdmin(User $user): JsonResponse
    {
        $sekarang = now()->timezone('Asia/Jakarta');
        $awalHari = $sekarang->copy()->startOfDay();
        $akhirHari = $sekarang->copy()->endOfDay();

        $pendingTotal = (int) Pinjam::where('status', 'pending')->count();
        $pending = Pinjam::with(['user', 'alat'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->take(5)
            ->get();

        $terlambatTotal = (int) Pinjam::terlambat()->count();
        $terlambat = Pinjam::with(['user', 'alat'])
            ->terlambat()
            ->orderByDesc('dikembalikan_saat')
            ->take(5)
            ->get();

        // Alat yang sudah disetujui dan dijadwalkan diambil hari ini.
        $ambilHariIni = Pinjam::with(['user', 'alat', 'guru'])
            ->where('status', 'disetuji')
            ->whereBetween('dipinjam_saat', [$awalHari, $akhirHari])
            ->orderBy('dipinjam_saat')
            ->get();

        // Alat yang harus kembali hari ini dan tenggatnya belum lewat.
        $kembaliHariIni = Pinjam::with(['user', 'alat'])
            ->where('status', 'dipinjam')
            ->whereBetween('dikembalikan_saat', [$awalHari, $akhirHari])
            ->orderBy('dikembalikan_saat')
            ->get()
            ->filter(function (Pinjam $pinjam) use ($sekarang) {
                return Carbon::parse($pinjam->dikembalikan_saat)->gte($sekarang);
            })
            ->values();

        // Posisi baca terakhir per percakapan untuk admin ini.
        $readPositions = DB::table('conversation_reads')
            ->where('reader_id', $user->id)
            ->pluck('last_read_message_id', 'user_id');

        $pesanBaru = Message::with(['sender', 'student'])
            ->whereColumn('sender_id', 'user_id')
            ->orderByDesc('id')
            ->take(80)
            ->get()
            ->filter(function (Message $message) use ($readPositions) {
                $terakhirDibaca = (int) ($readPositions[$message->user_id] ?? 0);

                return $message->id > $terakhirDibaca;
            })
            ->unique('user_id')
            ->take(5)
            ->values();

        $unreadChat = Message::unreadCountFor($user);

        $sections = [];

        if ($ambilHariIni->isNotEmpty()) {
            $sections[] = [
                'key' => 'ambil_hari_ini',
                'label' => 'Pengambilan hari ini',
                'count' => $ambilHariIni->count(),
                'url' => route('approval.index'),
                'items' => $ambilHariIni->take(5)->map(function (Pinjam $pinjam) {
                    $jadwal = Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta');
                    $batas = JadwalSekolah::kembaliTerakhir($jadwal);

                    return [
                        'title' => ($pinjam->user?->name ?? 'Siswa') . ' · ' . ($pinjam->alat?->nama ?? 'Alat'),
                        'meta' => 'Ambil pukul ' . $jadwal->format('H.i')
                            . ($batas ? ' · paling lambat ' . $batas->format('H.i') : '')
                            . ' · ' . $pinjam->jumlah_minta . ' unit',
                        'url' => route('approval.index'),
                    ];
                })->all(),
            ];
        }

        if ($kembaliHariIni->isNotEmpty()) {
            $sections[] = [
                'key' => 'kembali_hari_ini',
                'label' => 'Pengembalian hari ini',
                'count' => $kembaliHariIni->count(),
                'url' => route('dashboard') . '#tabel-transaksi',
                'items' => $kembaliHariIni->take(5)->map(function (Pinjam $pinjam) use ($sekarang) {
                    $tenggat = Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta');
                    $sisa = (int) ceil($sekarang->diffInMinutes($tenggat, false));

                    return [
                        'title' => ($pinjam->user?->name ?? 'Siswa') . ' · ' . ($pinjam->alat?->nama ?? 'Alat'),
                        'meta' => 'Tenggat pukul ' . $tenggat->format('H.i')
                            . ($sisa > 0 ? ' · sisa ' . $sisa . ' menit' : ''),
                        'url' => route('dashboard') . '#tabel-transaksi',
                    ];
                })->all(),
            ];
        }

        if ($pendingTotal > 0) {
            $sections[] = [
                'key' => 'pending',
                'label' => 'Menunggu persetujuan',
                'count' => $pendingTotal,
                'url' => route('approval.index'),
                'items' => $pending->map(fn (Pinjam $pinjam) => [
                    'title' => ($pinjam->user?->name ?? 'Siswa') . ' · ' . ($pinjam->alat?->nama ?? 'Alat'),
                    'meta' => $pinjam->jumlah_minta . ' unit · diajukan '
                        . Carbon::parse($pinjam->created_at)
                            ->timezone('Asia/Jakarta')
                            ->locale('id')
                            ->translatedFormat('d M Y, H.i'),
                    'url' => route('approval.index'),
                ])->all(),
            ];
        }

        if ($unreadChat > 0) {
            $sections[] = [
                'key' => 'chat',
                'label' => 'Chat belum dibaca',
                'count' => $unreadChat,
                'url' => route('admin.chat.index'),
                'items' => $pesanBaru->map(fn (Message $message) => [
                    'title' => $message->sender?->name ?? $message->student?->name ?? 'Siswa',
                    'meta' => Str::limit($this->cuplikanPesan($message), 70),
                    'url' => route('admin.chat.show', $message->user_id),
                ])->all(),
            ];
        }

        if ($terlambatTotal > 0) {
            $sections[] = [
                'key' => 'terlambat',
                'label' => 'Melewati tenggat',
                'count' => $terlambatTotal,
                'url' => route('dashboard') . '#tabel-transaksi',
                'items' => $terlambat->map(function (Pinjam $pinjam) {
                    $tenggat = $pinjam->dikembalikan_saat
                        ? Carbon::parse($pinjam->dikembalikan_saat)
                            ->timezone('Asia/Jakarta')
                            ->locale('id')
                            ->translatedFormat('d M Y, H.i')
                        : '-';

                    return [
                        'title' => ($pinjam->user?->name ?? 'Siswa') . ' · ' . ($pinjam->alat?->nama ?? 'Alat'),
                        'meta' => ($pinjam->terlambat_label ?? 'Terlambat') . ' · tenggat ' . $tenggat,
                        'url' => route('dashboard') . '#tabel-transaksi',
                    ];
                })->all(),
            ];
        }

        return response()->json([
            'total' => $pendingTotal + $unreadChat + $terlambatTotal
                + $ambilHariIni->count() + $kembaliHariIni->count(),
            'sections' => $sections,
            'updated_at' => $sekarang->format('H.i'),
        ]);
    }

    /**
     * Notifikasi siswa: waktu pengambilan, waktu pengembalian, pengajuan
     * pending, dan chat dari admin yang belum dibaca.
     */
    private function untukSiswa(User $user): JsonResponse
    {
        $sekarang = now()->timezone('Asia/Jakarta');
        $milikSiswa = fn () => Pinjam::where('user_id', $user->id);

        $pendingTotal = (int) $milikSiswa()->where('status', 'pending')->count();
        $pending = $milikSiswa()->with('alat')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $disetuji = $milikSiswa()->with('alat')
            ->where('status', 'disetuji')
            ->orderBy('dipinjam_saat')
            ->get();

        $aktif = $milikSiswa()->with('alat')
            ->where('status', 'dipinjam')
            ->orderBy('dikembalikan_saat')
            ->get();

        // Sudah masuk jam pengambilan pada tanggal yang dijadwalkan.
        $waktunyaAmbil = $disetuji->filter(function (Pinjam $pinjam) use ($sekarang) {
            if (! $pinjam->dipinjam_saat) {
                return false;
            }

            $jadwal = Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta');

            if (! $jadwal->isSameDay($sekarang)) {
                return false;
            }

            $mulai = JadwalSekolah::ambilMulai($jadwal);
            $batas = JadwalSekolah::kembaliTerakhir($jadwal);

            return $sekarang->gte($mulai) && (! $batas || $sekarang->lte($batas));
        })->values();

        $siapAmbil = $disetuji
            ->reject(fn (Pinjam $pinjam) => $waktunyaAmbil->contains('id', $pinjam->id))
            ->values();

        $terlambatKembali = $aktif->filter(function (Pinjam $pinjam) use ($sekarang) {
            if (! $pinjam->dikembalikan_saat) {
                return false;
            }

            return $sekarang->gt(Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta'));
        })->values();

        $segeraKembali = $aktif->filter(function (Pinjam $pinjam) use ($sekarang) {
            if (! $pinjam->dikembalikan_saat) {
                return false;
            }

            $tenggat = Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta');

            return $tenggat->isSameDay($sekarang)
                && $sekarang->lte($tenggat)
                && $sekarang->gte($tenggat->copy()->subMinutes(self::MENIT_PENGINGAT_KEMBALI));
        })->values();

        // Posisi baca terakhir percakapan siswa ini.
        $readPosition = (int) DB::table('conversation_reads')
            ->where('user_id', $user->id)
            ->where('reader_id', $user->id)
            ->value('last_read_message_id');

        $pesanBaru = Message::with('sender')
            ->where('user_id', $user->id)
            ->whereColumn('sender_id', '!=', 'user_id')
            ->where('id', '>', $readPosition)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $unreadChat = Message::unreadCountFor($user);

        $sections = [];

        if ($terlambatKembali->isNotEmpty()) {
            $sections[] = [
                'key' => 'kembali',
                'label' => 'Terlambat dikembalikan',
                'count' => $terlambatKembali->count(),
                'url' => route('pinjam.bukti'),
                'items' => $terlambatKembali->take(5)->map(function (Pinjam $pinjam) {
                    $tenggat = Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta');

                    return [
                        'title' => ($pinjam->alat?->nama ?? 'Alat') . ' · ' . $pinjam->jumlah_minta . ' unit',
                        'meta' => ($pinjam->terlambat_label ?? 'Terlambat')
                            . ' · tenggat ' . $tenggat->locale('id')->translatedFormat('d M Y, H.i'),
                        'url' => route('pinjam.bukti'),
                    ];
                })->all(),
            ];
        }

        if ($segeraKembali->isNotEmpty()) {
            $sections[] = [
                'key' => 'kembali_segera',
                'label' => 'Waktunya mengembalikan',
                'count' => $segeraKembali->count(),
                'url' => route('pinjam.bukti'),
                'items' => $segeraKembali->take(5)->map(function (Pinjam $pinjam) use ($sekarang) {
                    $tenggat = Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta');
                    $sisa = (int) ceil($sekarang->diffInMinutes($tenggat, false));

                    return [
                        'title' => ($pinjam->alat?->nama ?? 'Alat') . ' · ' . $pinjam->jumlah_minta . ' unit',
                        'meta' => 'Sisa ' . max(0, $sisa) . ' menit · tenggat pukul ' . $tenggat->format('H.i'),
                        'url' => route('pinjam.bukti'),
                    ];
                })->all(),
            ];
        }

        if ($waktunyaAmbil->isNotEmpty()) {
            $sections[] = [
                'key' => 'ambil',
                'label' => 'Waktunya mengambil',
                'count' => $waktunyaAmbil->count(),
                'url' => route('pinjam.bukti'),
                'items' => $waktunyaAmbil->take(5)->map(function (Pinjam $pinjam) {
                    $jadwal = Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta');
                    $batas = JadwalSekolah::kembaliTerakhir($jadwal);

                    return [
                        'title' => ($pinjam->alat?->nama ?? 'Alat') . ' · ' . $pinjam->jumlah_minta . ' unit',
                        'meta' => 'Ambil hari ini pukul ' . $jadwal->format('H.i')
                            . ($batas ? ' · paling lambat ' . $batas->format('H.i') : ''),
                        'url' => route('pinjam.bukti'),
                    ];
                })->all(),
            ];
        }

        if ($siapAmbil->isNotEmpty()) {
            $sections[] = [
                'key' => 'ambil_nanti',
                'label' => 'Siap diambil',
                'count' => $siapAmbil->count(),
                'url' => route('pinjam.bukti'),
                'items' => $siapAmbil->take(5)->map(function (Pinjam $pinjam) {
                    $jadwal = $pinjam->dipinjam_saat
                        ? Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta')
                        : null;

                    $meta = 'Menunggu jadwal pengambilan.';

                    if ($jadwal) {
                        $batas = JadwalSekolah::kembaliTerakhir($jadwal);
                        $meta = 'Ambil ' . $jadwal->locale('id')->translatedFormat('d M Y')
                            . ' · paling cepat pukul '
                            . str_replace(':', '.', config('school.jam_ambil_mulai', '06:30'))
                            . ($batas ? ', paling lambat pukul ' . $batas->format('H.i') : '');
                    }

                    return [
                        'title' => ($pinjam->alat?->nama ?? 'Alat') . ' · ' . $pinjam->jumlah_minta . ' unit',
                        'meta' => $meta,
                        'url' => route('pinjam.bukti'),
                    ];
                })->all(),
            ];
        }

        if ($pendingTotal > 0) {
            $sections[] = [
                'key' => 'pending',
                'label' => 'Menunggu persetujuan',
                'count' => $pendingTotal,
                'url' => route('dashboard'),
                'items' => $pending->map(fn (Pinjam $pinjam) => [
                    'title' => ($pinjam->alat?->nama ?? 'Alat') . ' · ' . $pinjam->jumlah_minta . ' unit',
                    'meta' => 'Diajukan '
                        . Carbon::parse($pinjam->created_at)
                            ->timezone('Asia/Jakarta')
                            ->locale('id')
                            ->translatedFormat('d M Y, H.i'),
                    'url' => route('dashboard'),
                ])->all(),
            ];
        }

        if ($unreadChat > 0) {
            $sections[] = [
                'key' => 'chat',
                'label' => 'Chat belum dibaca',
                'count' => $unreadChat,
                'url' => route('chat.index'),
                'items' => $pesanBaru->map(fn (Message $message) => [
                    'title' => $message->sender?->name ?? 'Admin',
                    'meta' => Str::limit($this->cuplikanPesan($message), 70),
                    'url' => route('chat.index'),
                ])->all(),
            ];
        }

        return response()->json([
            'total' => $terlambatKembali->count() + $segeraKembali->count()
                + $waktunyaAmbil->count() + $siapAmbil->count()
                + $pendingTotal + $unreadChat,
            'sections' => $sections,
            'updated_at' => $sekarang->format('H.i'),
        ]);
    }

    private function cuplikanPesan(Message $message): string
    {
        $body = trim((string) $message->body);

        return $body !== '' ? $body : '[Gambar]';
    }
}
