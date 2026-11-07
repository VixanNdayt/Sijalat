<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PinjamController extends Controller
{
    /**
     * Jam operasional peminjaman sesuai jadwal sekolah.
     * Senin 08.00-16.30, Selasa-Kamis 07.00-16.35, Jumat 08.00-14.45.
     */
    private const JAM_SEKOLAH = [
        1 => ['08:00', '16:30'],
        2 => ['07:00', '16:35'],
        3 => ['07:00', '16:35'],
        4 => ['07:00', '16:35'],
        5 => ['08:00', '14:45'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $alats = Alat::where('jumlah_stok', '>', 0)->get();

        // Daftar jenis alat untuk filter, diambil dari data yang benar-benar ada.
        $jenisAlats = $alats
            ->pluck('jenis')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function ($jenis) {
                $label = ucwords(str_replace('_', ' ', $jenis));
                $label = str_replace(['Pc', 'Iot'], ['PC', 'IoT'], $label);

                return [
                    'value' => strtolower($jenis),
                    'label' => $label,
                ];
            });

        // Pulihkan isi keranjang ketika validasi gagal (old input)
        $oldCart = [];
        $oldId = old('alat_id', []);
        $oldQty = old('jumlah_minta', []);

        if (is_array($oldId)) {
            foreach ($oldId as $index => $id) {
                $oldCart[] = [
                    'id'  => (int) $id,
                    'qty' => (int) ($oldQty[$index] ?? 1),
                ];
            }
        }

        return view('pinjam.create', compact('alats', 'oldCart', 'jenisAlats'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alat_id'           => 'required|array',
            'alat_id.*'         => 'required|exists:alats,id',
            'jumlah_minta'      => 'required|array',
            'jumlah_minta.*'    => 'required|integer|min:1',
            'dipinjam_saat'     => 'required|date',
            'ruangan'           => 'required|string|max:255',
            'dikembalikan_saat' => 'required|date|after_or_equal:dipinjam_saat',
            'deskripsi_minta'   => 'required|string|max:255',
        ]);

        $validator->after(function ($validator) use ($request) {
            // --- 1. Jumlah yang diminta tidak boleh melebihi stok tersedia ---
            $diminta = [];

            foreach ((array) $request->alat_id as $index => $id) {
                $id = (int) $id;
                $diminta[$id] = ($diminta[$id] ?? 0) + (int) ($request->jumlah_minta[$index] ?? 0);
            }

            $daftarAlat = Alat::whereIn('id', array_keys($diminta))->get()->keyBy('id');

            foreach ($diminta as $id => $jumlah) {
                $alat = $daftarAlat->get($id);

                if (! $alat) {
                    continue;
                }

                if ($jumlah > $alat->jumlah_stok) {
                    $validator->errors()->add(
                        'jumlah_minta',
                        "Jumlah {$alat->nama} melebihi stok tersedia ({$alat->jumlah_stok})."
                    );
                }
            }

            // --- 2. Waktu peminjaman hanya di dalam jam sekolah (Senin-Jumat) ---
            $waktu = [
                'dipinjam_saat'     => $request->filled('dipinjam_saat') ? Carbon::parse($request->dipinjam_saat) : null,
                'dikembalikan_saat' => $request->filled('dikembalikan_saat') ? Carbon::parse($request->dikembalikan_saat) : null,
            ];

            foreach ($waktu as $field => $saat) {
                if (! $saat) {
                    continue;
                }

                $aturan = self::JAM_SEKOLAH[$saat->dayOfWeekIso] ?? null;

                if (! $aturan) {
                    $pesan = 'Peminjaman hanya dapat dilakukan pada hari Senin sampai Jumat.';
                } else {
                    $jam = $saat->format('H:i');

                    if ($jam >= $aturan[0] && $jam <= $aturan[1]) {
                        continue;
                    }

                    $pesan = 'Jam peminjaman harus di antara '
                        . str_replace(':', '.', $aturan[0]) . ' - '
                        . str_replace(':', '.', $aturan[1])
                        . ' sesuai jam sekolah.';
                }

                // Hindari pesan yang sama muncul dua kali (waktu ambil & kembali).
                if (! in_array($pesan, $validator->errors()->all(), true)) {
                    $validator->errors()->add($field, $pesan);
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        foreach ($request->alat_id as $index => $id_alat) {
            Pinjam::create([
                'kode_pinjam'       => Pinjam::generateKodePinjam(),
                'qr_ambil_token'    => Pinjam::generateQrToken(),
                'qr_kembali_token'  => Pinjam::generateQrToken(),
                'user_id'           => Auth::id(),
                'alat_id'           => $id_alat,
                'jumlah_minta'      => $request->jumlah_minta[$index],
                'dipinjam_saat'     => $request->dipinjam_saat,
                'ruangan'           => $request->ruangan,
                'dikembalikan_saat' => $request->dikembalikan_saat,
                'deskripsi_minta'   => $request->deskripsi_minta,
                'status'            => 'pending',
            ]);
        }


        return redirect()->route('dashboard')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Pinjam $pinjam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pinjam $pinjam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pinjam $pinjam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pinjam $pinjam)
    {
        // Hanya pemilik request yang boleh menghapus
        if ($pinjam->user_id !== Auth::id()) {
            return redirect()->route('dashboard')
                ->withErrors(['hapus' => 'Kamu tidak berhak menghapus pengajuan ini.']);
        }

        // Hanya pengajuan yang masih pending yang bisa dibatalkan
        if ($pinjam->status !== 'pending') {
            return redirect()->route('dashboard')
                ->withErrors(['hapus' => 'Pengajuan ini sudah diproses dan tidak bisa dihapus.']);
        }

        $pinjam->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }
    
}
