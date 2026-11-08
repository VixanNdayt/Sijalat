<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Homepage publik: katalog, pencarian, statistik, dan ringkasan aktivitas.
     */
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $jenis = $request->query('jenis', '');
        $q = is_string($q) ? trim($q) : '';
        $jenis = is_string($jenis) ? trim($jenis) : '';

        $allAlats = Alat::orderBy('nama')->get();

        // Kategori diambil dari data yang benar-benar ada.
        $kategori = $allAlats
            ->filter(fn (Alat $alat) => filled($alat->jenis))
            ->groupBy('jenis')
            ->map(fn ($items, $value) => [
                'value' => (string) $value,
                'label' => Alat::jenisLabel((string) $value),
                'count' => $items->count(),
            ])
            ->sortBy('label')
            ->values();

        $alats = $allAlats;

        if ($jenis !== '' && $jenis !== 'semua') {
            $alats = $alats->where('jenis', $jenis);
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);

            $alats = $alats->filter(function (Alat $alat) use ($needle) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $alat->nama,
                    $alat->code,
                    $alat->jenis,
                    Alat::jenisLabel($alat->jenis),
                    $alat->description,
                    $alat->lokasiLabel(),
                ])));

                return str_contains($haystack, $needle);
            });
        }

        $alats = $alats->values();

        // Tampilkan beberapa alat sebagai contoh agar beranda tidak terlalu
        // panjang, kecuali pengguna sedang mencari/memfilter atau meminta semua.
        $semua = $request->boolean('semua');
        $filterAktif = $q !== '' || ($jenis !== '' && $jenis !== 'semua');
        $batasContoh = 6;
        $totalHasil = $alats->count();
        $dibatasi = ! $semua && ! $filterAktif && $totalHasil > $batasContoh;
        $tampilAlats = $dibatasi ? $alats->take($batasContoh)->values() : $alats;

        // Statistik ringkas inventaris.
        $totalAlat = $allAlats->count();
        $totalUnit = (int) $allAlats->sum(fn (Alat $alat) => $alat->totalFisik());
        $tersedia = (int) $allAlats->sum(fn (Alat $alat) => (int) $alat->jumlah_stok);
        $perawatan = (int) $allAlats->sum(
            fn (Alat $alat) => (int) $alat->kondisi_rusak + (int) $alat->kondisi_maintenance
        );
        $dipinjam = (int) Pinjam::query()
            ->whereIn('status', ['disetuji', 'dipinjam'])
            ->sum('jumlah_minta');

        // Ringkasan aktivitas untuk pengguna yang sudah masuk.
        $user = Auth::user();
        $activeLoans = collect();
        $pendingApproval = 0;

        if ($user) {
            if ($user->isAdmin()) {
                $pendingApproval = Pinjam::where('status', 'pending')->count();
            } else {
                $activeLoans = Pinjam::with('alat')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'disetuji', 'dipinjam'])
                    ->orderBy('dikembalikan_saat')
                    ->orderBy('created_at')
                    ->get();
            }
        }

        // Data untuk keranjang multi-item di sisi klien.
        $alatJson = $allAlats->map(fn (Alat $alat) => [
            'id' => (int) $alat->id,
            'nama' => $alat->nama,
            'code' => $alat->code,
            'jenis' => Alat::jenisLabel($alat->jenis),
            'lokasi' => $alat->lokasiLabel(),
            'max' => (int) $alat->jumlah_stok,
            'img' => $alat->img
                ? asset($alat->img)
                : asset('svg/equipment-placeholder.svg'),
        ])->values();

        $lab = config('lab');

        return view('home', compact(
            'q',
            'jenis',
            'alats',
            'tampilAlats',
            'totalHasil',
            'dibatasi',
            'semua',
            'kategori',
            'totalAlat',
            'totalUnit',
            'tersedia',
            'dipinjam',
            'perawatan',
            'activeLoans',
            'pendingApproval',
            'alatJson',
            'lab'
        ));
    }
}
