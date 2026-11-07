<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>Login &middot; SIJALAT</title>
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
                <h1>Login ke SIJALAT</h1>

                <div class="login-form">
                    <h2>Selamat datang kembali</h2>

                    @if (session('status'))
                        <div class="alert-success">{{ session('status') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert-error">{{ session('error') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert-error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" class="form">
                        @csrf

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Masukkan email"
                                required
                                autofocus
                                autocomplete="username"
                            >

                            @error('email')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                            >

                            @error('password')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-options">
                            <label class="remember-row" for="remember">
                                <input type="checkbox" id="remember" name="remember" value="1">
                                <span>Ingat saya</span>
                            </label>

                            <a class="small-link" href="{{ route('password.request') }}">Lupa password?</a>
                        </div>

                        <input type="submit" value="Masuk">
                    </form>

                    <p class="form-footer">
                        Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>
