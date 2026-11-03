<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $alats = Alat::orderBy('created_at', 'desc')->get();
        return view('admin.alat', compact('alats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama'                => 'required|string',
            'description'         => 'required|string',
            'code'                => 'required|string|unique:alats,code',
            'kondisi_baik'        => 'required|integer|min:0',
            'kondisi_rusak'       => 'required|integer|min:0',
            'kondisi_maintenance' => 'required|integer|min:0',
            'img'                => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $file = $request->file('img');
        $namaFile = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('equipment'), $namaFile);
        $pathFoto = 'equipment/' . $namaFile;
        
        // Hitung total Qty secara otomatis
        $totalQty = $request->kondisi_baik + $request->kondisi_rusak + $request->kondisi_maintenance;

        Alat::create([
            'nama'                => $request->nama,
            'description'         => $request->description,
            'kondisi_baik'        => $request->kondisi_baik,
            'kondisi_rusak'       => $request->kondisi_rusak,
            'kondisi_maintenance' => $request->kondisi_maintenance,
            'jumlah_stok'         => $totalQty, 
            'img'                => $pathFoto,
            'code'                => $request->code,
        ]);

        return back()->with('success', 'Alat berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Alat $alat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alat $alat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama'                => 'required|string',
            'description'         => 'required|string',
            'kondisi_baik'        => 'required|integer|min:0',
            'code'                => 'required|string|unique:alats,code,' . $alat->code . ',code',
            'kondisi_rusak'       => 'required|integer|min:0',
            'kondisi_maintenance' => 'required|integer|min:0',
            'img'                => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $totalQty = $request->kondisi_baik + $request->kondisi_rusak + $request->kondisi_maintenance;

        $alat->nama = $request->nama;
        $alat->description = $request->description;
        $alat->code = $request->code;
        $alat->kondisi_baik = $request->kondisi_baik;
        $alat->kondisi_rusak = $request->kondisi_rusak;
        $alat->kondisi_maintenance = $request->kondisi_maintenance;
        $alat->jumlah_stok = $totalQty;

        // Jika admin mengunggah foto baru, ganti yang lama
        if ($request->hasFile('img')) {
            // Hapus foto lama jika ada (opsional)
            if ($alat->img && file_exists(public_path($alat->img))) {
                unlink(public_path($alat->img));
            }

            $file = $request->file('img');
            $namaFile = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('equipment'), $namaFile);
            $alat->img = 'equipment/' . $namaFile;
        }
        $alat->save();
        return back()->with('success', 'Data alat berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $alat = Alat::findOrFail($id);

            // 1. Cek dan hapus file fisik gambar dari folder public/equipment
            // Pastikan kita memanggil kolom 'img', bukan 'foto'
            if ($alat->img && file_exists(public_path($alat->img))) {
                unlink(public_path($alat->img));
            }

            // 2. Hapus data alat dari database
            $alat->delete();

            return back()->with('success', 'Alat berhasil dihapus secara permanen!');

        } catch (\Illuminate\Database\QueryException $e) {
            // 3. Menangkap error jika barang tidak bisa dihapus karena relasi foreign key (sedang dipinjam)
            return back()->withErrors([
                'hapus' => 'Gagal menghapus! Alat ini tidak bisa dihapus karena masih tercatat di dalam riwayat peminjaman siswa.'
            ]);
        }
    }
}
