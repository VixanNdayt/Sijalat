<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SchoolEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'digits_between:1,30', 'unique:users,nis'],
            'kelas' => ['required', 'string', 'max:50', Rule::in(config('school.classes', []))],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, new SchoolEmail],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'nis.unique' => 'NIS sudah dipakai akun lain.',
            'nis.digits_between' => 'NIS harus berupa angka 1 sampai 30 digit.',
            'kelas.in' => 'Kelas yang dipilih tidak valid. Pilih salah satu kelas yang tersedia.',
            'email.unique' => 'Email ini sudah terdaftar.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'nis' => $data['nis'],
            'kelas' => $data['kelas'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        // Setelah mendaftar, langsung tampilkan homepage.
        return redirect()
            ->route('home')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di SIJALAT!');
    }
}
