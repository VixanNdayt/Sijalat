<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>Daftar &middot; SIJALAT</title>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">

        @stack('styles')
    </head>
    <body>
        <main class="page">
            <section class="hero1">
                <div>
                    <div class="logo">
                        <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="Logo SIJALAT">
                        <h3>SIJALAT</h3>
                    </div>
                    <h2>Pinjam alat dengan cepat</h2>
                    <p>Sistem Peminjaman Alat SIJA &mdash; ajukan, ambil, dan kembalikan alat dalam satu tempat.</p>
                </div>
                <div></div>
            </section>

            <section class="login">
                <h1>Daftar Akun SIJALAT</h1>

                <div class="login-form is-register">
                    <h2>Buat akun siswa</h2>

                    @if ($errors->any())
                        <div class="alert-error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST" class="form">
                        @csrf

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nama lengkap</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Masukkan nama lengkap"
                                    required
                                    autofocus
                                    autocomplete="name"
                                >

                                @error('name')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="nis">NIS</label>
                                <input
                                    type="text"
                                    id="nis"
                                    name="nis"
                                    value="{{ old('nis') }}"
                                    placeholder="Nomor induk siswa"
                                    inputmode="numeric"
                                    required
                                >

                                @error('nis')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="kelas">Kelas</label>
                            <select name="kelas" id="kelas" required data-custom-select>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach (config('school.classes', []) as $kelas)
                                    <option value="{{ $kelas }}" @selected(old('kelas') === $kelas)>{{ $kelas }}</option>
                                @endforeach
                            </select>
                            @error('kelas')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email sekolah</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="NIS&#64;{{ config('auth.student_email_domain') }}"
                                required
                                autocomplete="username"
                            >

                            @error('email')
                                <span class="error-text">{{ $message }}</span>
                            @enderror

                            <p class="input-hint">
                                Wajib memakai email <strong>&#64;{{ config('auth.student_email_domain') }}</strong>.
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                required
                                autocomplete="new-password"
                            >

                            @error('password')
                                <span class="error-text">{{ $message }}</span>
                            @enderror

                            <p class="input-hint">
                                Minimal 10 karakter, kombinasi huruf besar, huruf kecil, dan angka.
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi password</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Ulangi password di atas"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <input type="submit" value="Daftar">
                    </form>

                    <p class="form-footer">
                        Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
                    </p>
                </div>
            </section>
        </main>

        <script src="{{ asset('js/custom-select.js') }}?v=4"></script>
    </body>
</html>
