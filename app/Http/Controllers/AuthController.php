<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Menampilkan halaman form register
    public function showRegister()
    {
        return view('auth.register');
    }

    // Memproses data pendaftaran dari form
    public function register(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'nis'      => 'required|numeric|unique:users,nis',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        // 2. Simpan data siswa ke MySQL
        $user = User::create([
            'name'     => $validated['name'],
            'nis'      => $validated['nis'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 3. Otomatis loginkan siswa setelah daftar
        Auth::login($user);

        // 4. Arahkan ke dashboard
        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat!');
    }
}