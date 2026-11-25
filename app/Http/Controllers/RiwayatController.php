<?php

namespace App\Http\Controllers;

use App\Models\Pinjam;
use App\Support\RiwayatExport;
use App\Support\StrukExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RiwayatController extends Controller
{
    /**
     * Cetak / unduh rincian satu transaksi peminjaman milik siswa.
     */
    public function cetak(Request $request, Pinjam $pinjam)
    {
        $this->pastikanBoleh($pinjam);

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
        $this->pastikanBoleh($pinjam);

        $pdf = StrukExport::pdf($pinjam);

        return $this->kirimPdf(
            $pdf,
            'Struk-' . ($pinjam->kode_pinjam ?: $pinjam->id) . '.pdf',
            $request->boolean('download')
        );
    }

    private function pastikanBoleh(Pinjam $pinjam): void
    {
        // Policy memeriksa kepemilikan siswa atau hak admin di sisi server.
        Gate::authorize('view', $pinjam);
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
