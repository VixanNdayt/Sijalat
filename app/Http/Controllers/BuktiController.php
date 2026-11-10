<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuktiController extends Controller
{
    /**
     * Bukti baru disimpan di disk privat. File lama (sebelum perbaikan
     * keamanan) masih tersimpan di disk publik, sehingga tetap dibaca
     * sebagai fallback agar riwayat tidak hilang.
     */
    private const DISK_BUKTI = 'local';

    private const DISK_BUKTI_LEGACY = 'public';

    /**
     * Daftar bukti pengambilan dan pengembalian untuk admin/guru.
     */
    public function index(Request $request)
    {
        $jenis = $request->query('jenis', 'semua');
        $jenis = is_string($jenis) ? $jenis : 'semua';

        if (! in_array($jenis, ['semua', 'ambil', 'kembali'], true)) {
            $jenis = 'semua';
        }

        $cari = $request->query('cari', '');
        $cari = is_string($cari) ? trim($cari) : '';

        $dasar = Pinjam::query()->where(function ($query) {
            $query->whereNotNull('bukti_ambil')
                ->orWhereNotNull('bukti_kembali');
        });

        $statistik = (clone $dasar)
            ->selectRaw(
                'COUNT(*) as transaksi,
                 SUM(bukti_ambil IS NOT NULL) as ambil,
                 SUM(bukti_kembali IS NOT NULL) as kembali,
                 SUM(bukti_ambil IS NOT NULL AND bukti_kembali IS NOT NULL) as lengkap'
            )
            ->first();

        $ringkasan = [
            'transaksi' => (int) ($statistik->transaksi ?? 0),
            'ambil' => (int) ($statistik->ambil ?? 0),
            'kembali' => (int) ($statistik->kembali ?? 0),
            'lengkap' => (int) ($statistik->lengkap ?? 0),
        ];

        $query = (clone $dasar)->with(['user', 'alat']);

        if ($jenis === 'ambil') {
            $query->whereNotNull('bukti_ambil');
        } elseif ($jenis === 'kembali') {
            $query->whereNotNull('bukti_kembali');
        }

        if ($cari !== '') {
            $suka = '%' . $cari . '%';

            $query->where(function ($sub) use ($suka) {
                $sub->where('kode_pinjam', 'like', $suka)
                    ->orWhereHas('user', function ($user) use ($suka) {
                        $user->where('name', 'like', $suka)
                            ->orWhere('nis', 'like', $suka)
                            ->orWhere('kelas', 'like', $suka);
                    })
                    ->orWhereHas('alat', function ($alat) use ($suka) {
                        $alat->where('nama', 'like', $suka)
                            ->orWhere('code', 'like', $suka);
                    });
            });
        }

        $pinjams = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        // Tandai apakah file bukti benar-benar ada di disk. Path bisa saja
        // tersimpan di database tetapi filenya sudah terhapus.
        $pinjams->getCollection()->each(function (Pinjam $pinjam) {
            $pinjam->setAttribute('file_ambil', $this->fileTersedia($pinjam->bukti_ambil));
            $pinjam->setAttribute('file_kembali', $this->fileTersedia($pinjam->bukti_kembali));
        });

        return view('admin.bukti', compact('pinjams', 'jenis', 'cari', 'ringkasan'));
    }

    /**
     * Sajikan file bukti hanya untuk admin yang sudah login.
     */
    public function foto(Pinjam $pinjam, string $jenis)
    {
        abort_unless(in_array($jenis, ['ambil', 'kembali'], true), 404);

        $path = $jenis === 'ambil' ? $pinjam->bukti_ambil : $pinjam->bukti_kembali;
        $disk = $this->diskBukti($path);

        if ($disk === null) {
            abort(404);
        }

        return Storage::disk($disk)->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function fileTersedia(?string $path): bool
    {
        return $this->diskBukti($path) !== null;
    }

    private function pathAman(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && str_starts_with($path, 'bukti_transaksi/')
            && ! str_contains($path, '..');
    }

    /**
     * Tentukan disk yang benar-benar menyimpan file bukti.
     * Prioritaskan disk privat, lalu fallback ke file lama di disk publik.
     */
    private function diskBukti(?string $path): ?string
    {
        if (! $this->pathAman($path)) {
            return null;
        }

        foreach ([self::DISK_BUKTI, self::DISK_BUKTI_LEGACY] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
