<?php

namespace App\Http\Controllers;

use App\Models\alat;
use App\Models\pinjam;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'user_id' => 'requiared|exists:user,id',
            'alat_id' => 'required|exists:alat,id',
            'jumlah_minta' => 'required|integer|min:1',
            'dipinjam_saat' => 'required|date|after_or_equal:today',
            'deskripsi_minta' => 'required|string|max:255',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(pinjam $pinjam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(pinjam $pinjam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, pinjam $pinjam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(pinjam $pinjam)
    {
        //
    }
}
