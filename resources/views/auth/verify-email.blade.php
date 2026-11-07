<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>Verifikasi Email &middot; SIJALAT</title>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    </head>
    <body>
        <main class="auth-page">
            <section class="auth-card">
                <div class="logo" style="justify-content: center; margin-bottom: 18px;">
                    <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="Logo SIJALAT">
                    <h3>SIJALAT</h3>
                </div>

                <div class="auth-icon" aria-hidden="true">&#9993;</div>

                <h1>Verifikasi Email Kamu</h1>

                <p>
                    Kami sudah mengirim tautan verifikasi ke
                    <strong>{{ auth()->user()->email }}</strong>.
                    Klik tautan tersebut agar bisa mulai meminjam alat.
                </p>

                @if (session('status') === 'verification-link-sent')
                    <div class="alert-success">
                        Tautan verifikasi baru sudah dikirim. Cek inbox atau folder spam email sekolahmu.
                    </div>
                @elseif (session('status'))
                    <div class="alert-success">{{ session('status') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert-error">{{ session('error') }}</div>
                @endif

                <div class="auth-actions">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn-primary">
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="link-button">Keluar dari akun</button>
                    </form>
                </div>

                <p class="auth-note">
                    Email belum masuk? Tunggu beberapa menit, lalu kirim ulang.
                    Pastikan alamat emailmu benar melalui halaman profil setelah verifikasi.
                </p>
            </section>
        </main>
    </body>
</html>
