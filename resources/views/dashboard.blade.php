<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

        @stack('styles')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <button href="#">Dashboard</button>
                <button>Peminjaman</button>
                <button>Riwayat</button>
                <button>Report</button>
                <button>Guide</button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <button class="toggle-btn" id="toggleBtn">☰</button>
                <h2>SIJALAT</h2>
                <button class="profile-edit" href="profile">
                    <img src="{{ asset('svg/user_icon.svg') }}">
                    <p>{{ auth()->user()->name }} </p>
                </button>
            </nav>
            <script src="{{ asset('js/univ.js') }}"></script>
        </main>
    </body>
</html>