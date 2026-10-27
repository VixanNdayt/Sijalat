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
            <ul class="sidebar-menu">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="#">Peminjaman</a></li>
                <li><a href="3">Riwayat</a></li>
                <li><a href="3">Report</a></li>
                <li><a href="3">Guide</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <button class="toggle-btn" id="toggleBtn">☰</button>
                <h2>SIJALAT</h2>
                <div class="profile-edit">
                    <p>{{ auth()->user()->name }}</p>
                </div>
            </nav>

            <script src="{{ asset('js/univ.js') }}"></script>
        </main>
    </body>
</html>