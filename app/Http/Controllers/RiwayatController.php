<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use App\Support\RiwayatExport;
use App\Support\StrukExport;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    /**
     * Cetak / unduh rincian satu transaksi peminjaman milik siswa.
     */
    public function cetak(Request $request, Pinjam $pinjam)
    {
        $this->pastikanBoleh($request, $pinjam, 'mencetak riwayat');

        $pdf = RiwayatExport::pdf($pinjam);

        return $this->kirimPdf(
            $pdf,
            'Riwayat-' . ($pinjam->kode_pinjam ?: $pinjam->id) . '.pdf',
            $request->boolean('download')
        );
    }

    /**
     * Cetak / unduh struk transaksi bergaya nota toko.
     */
    public function struk(Request $request, Pinjam $pinjam)
    {
        $this->pastikanBoleh($request, $pinjam, 'mencetak struk');

        $pdf = StrukExport::pdf($pinjam);

        return $this->kirimPdf(
            $pdf,
            'Struk-' . ($pinjam->kode_pinjam ?: $pinjam->id) . '.pdf',
            $request->boolean('download')
        );
    }

    private function pastikanBoleh(Request $request, Pinjam $pinjam, string $aksi): void
    {
        $user = $request->user();

        if (! $user || ($user->role_user !== 'admin' && $pinjam->user_id !== $user->id)) {
            abort(403, 'Anda tidak berhak ' . $aksi . ' transaksi ini.');
        }
    }

    private function kirimPdf(string $pdf, string $filename, bool $download)
    {
        $disposition = $download ? 'attachment' : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
