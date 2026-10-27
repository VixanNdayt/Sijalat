<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT')</title>
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">

        @stack('styles')
    </head>
    <body>
         <main class="page">
            <section class="hero1">
                <div>
                    <div class="logo">
                        <h3>SIJALAT</h3>
                    </div>
                    <h2>Pinjam Alat dengan cepat</h2>
                    <p>Lakukan peminjaman secara cepat dan efisien</p>
                </div>
                <div>

                </div>
            </section>
            <section class="login">
                <h1>LOGIN TO SIJALAT</h1>
                <div class="login-form">
                    <h2>Welcome back to SIJALAT</h2>
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

                        <div>
                            <div class="form-group" style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 20px;">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" placeholder="Masukkan email" required>

                                <!-- error email -->
                                @error('email')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group" style="display: flex; flex-direction: column; gap: 5px;">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" placeholder="Masukkan password" required>

                                <!-- error password -->
                                @error('password')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>
                            <p style="font-size: 12px;">Belum punya akun? <a href="{{ route('register') }}">Sign up</a></p>
                        </div>
                        <br>
                        <input type="submit" value="Login" style="background-color: #004E39; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; text-align: center; margin-bottom: 20px;">
                    </form>
                </div>
            </section>
        </main>

    </body>
</html>