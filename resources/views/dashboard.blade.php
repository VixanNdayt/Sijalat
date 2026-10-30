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

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}">
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                <h2>SIJALAT</h2>

                <button class="toggle-btn" id="toggleBtn">☰</button>
            </nav>

            <div class="header-main">
                <div>
                    <h2>Dashboard</h2>
                    <p>Halo, {{ auth()->user()->name }}. Pantau aktivitas peminjaman dan kewajiban anda</p>
                </div>
                <button>
                    Ajukan Peminjaman
                </button>
            </div>

            <!-- 4Box -->
            <div class="information-box">
                <div class="inner-information">
                    <p>Sedang dipinjam</p>
                    <h2>{{ $sedangDipinjam }}</h2>
                    <p>Kembali pada waktu</p>
                </div>
                <div class="inner-information">
                    <p>Menunggu Approve</p>
                    <h2>{{ $menunggu }}</h2>
                    <p>Waktu permintaan : {{ $riwayat->created_at->format('d M Y') }}</p>
                </div>
                <div class="inner-information">
                    <p>Selesai dipinjam</p>
                    <h2>{{ $dikembalikan }}</h2>
                    <p>Presentase tepat waktu</p>
                </div>
                <div class="inner-information">
                    <p>Catatan Pelanggaram</p>
                    <h2>{{ $pelanggaran }}</h2>
                    <p>Terakhir pada</p>
                </div>
            </div>

            <!-- Barang yang sedang dipinjam -->
            <div class="sedang-dipinjam">
                <img src="{{ asset('svg/fluent_box-20-regular.svg') }}">
                <div class="text-sd">
                    <div class="inner-text-sd">
                        <p>Nama alat : </p>
                        <p>kondisi</p>
                    </div>
                    <div class="inner-text-sd">
                        <p>kode</p>
                        <p>Waktu peminjaman</p>
                        <p>Waktu pengembalian</p>
                    </div>
                </div>
            </div>

            <div>

            </div>
        </main>

        <!-- Sidebar -->
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

        <script src="{{ asset('js/univ.js') }}"></script>
    </body>
</html>