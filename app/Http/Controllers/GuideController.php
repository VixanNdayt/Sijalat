<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class GuideController extends Controller
{
    /**
     * Halaman panduan penggunaan SIJALAT (isi berbeda untuk siswa & guru).
     */
    public function index()
    {
        $user = Auth::user();

        return view('guide', [
            'isAdmin' => $user->isAdmin(),
        ]);
    }
}
