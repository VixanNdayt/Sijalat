<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'register-SIJALAT')</title>
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
                <h1>SIGN UP TO SIJALAT</h1>
                <div class="login-form">
                    <h2>Welcome to SIJALAT</h2>
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

                        <div>
                            <div class="form-group" style="display: flex; flex-direction: row; margin-bottom: 20px; justify-content: center;">

                                <div class="form-group-reg-1" style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="name">Nama:</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan namamu" required autofocus>

                                    <!-- error email -->
                                    @error('name')
                                        <span class="error-text">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group-reg-1" style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="nis">NIS</label>
                                    <input type="number" id="nis" name="nis" value="{{ old('nis') }}" required>

                                    <!-- error nis -->
                                    @error('nis')
                                        <span class="error-text">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group" style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 20px;">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required>

                                <!-- error password -->
                                @error('email')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 20px;">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" placeholder="Masukkan password" required>

                                <!-- error password -->
                                @error('password')
                                    <span class="error-text">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 20px;">
                                <label for="password_confirmation">Konfirmasi Password</label>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    required 
                                    placeholder="Ulangi password di atas"
                                >
                            </div>
                            <p style="font-size: 12px;">Sudah punya akun? <a href="{{ route('login') }}">Log in</a></p>
                        </div>
                        <br>
                        <input type="submit" value="register" style="background-color: #004E39; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; text-align: center; margin-bottom: 20px;">
                    </form>
                </div>
            </section>
        </main>

    </body>
</html>