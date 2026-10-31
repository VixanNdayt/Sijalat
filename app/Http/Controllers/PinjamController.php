<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PinjamController extends Controller
{
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
        return view('pinjam.create', compact('alats'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'alat_id'           => 'required|array',
            'alat_id.*'         => 'required|exists:alats,id',
            'jumlah_minta'      => 'required|array',
            'jumlah_minta.*'    => 'required|integer|min:1',
            'dipinjam_saat'     => 'required|date',
            'dikembalikan_saat' => 'required|date|after_or_equal:dipinjam_saat',
            'deskripsi_minta'   => 'required|string|max:255',
        ]);

        foreach ($request->alat_id as $index => $id_alat) {
            Pinjam::create([
                'user_id'           => Auth::id(),
                'alat_id'           => $id_alat,
                'jumlah_minta'      => $request->jumlah_minta[$index],
                'dipinjam_saat'     => $request->dipinjam_saat,
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
        //
    }
    
}
