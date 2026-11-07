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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'required|numeric|unique:users,nis',
            'kelas' => 'required|string|max:50',
            'email' => ['required', 'lowercase', 'email', 'unique:users,email', new SchoolEmail],
            'password' => ['required', Rules\Password::defaults(), 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'nis' => $validated['nis'],
            'kelas' => $validated['kelas'],
            'email' => $validated['email'],
            'role_user' => 'siswa',
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di SIJALAT!');
    }
}
