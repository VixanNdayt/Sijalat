<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\SchoolEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'required|numeric|unique:users,nis',
            'kelas' => 'required|string|max:50',
            'email' => ['required', 'lowercase', 'email', 'unique:users,email', new SchoolEmail],
            'password' => ['required', Rules\Password::defaults(), 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'nis' => $data['nis'],
            'kelas' => $data['kelas'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di SIJALAT!');
    }
}
