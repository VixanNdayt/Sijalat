<!DOCTYPE html>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard-a.css') }}">
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
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <div class="admin-container">
                <div class="header-teks">
                    <h2>Dashboard Admin</h2>
                    <p>Analisis setiap laporan dan data yang terjadi dalam kurun waktu</p>
                </div>
            
                <!-- 4 Kotak Ringkasan Data[cite: 7] -->
                <div class="card-grid">
                    <div class="summary-card border-blue">
                        <div class="icon"><x-icon name="trending-up" size="22" /></div>
                        <h3>{{ $totalMasuk }} Masuk</h3>
                        <p>Total Data</p>
                    </div>
                    <div class="summary-card border-green">
                        <div class="icon"><x-icon name="check-circle" size="22" /></div>
                        <h3>{{ $totalApproved }} Approved</h3>
                        <p>Total approved</p>
                    </div>
                    <div class="summary-card border-yellow">
                        <div class="icon"><x-icon name="clock" size="22" /></div>
                        <h3>{{ $totalPending }} pending</h3>
                        <p>Total pending</p>
                    </div>
                    <div class="summary-card border-red">
                        <div class="icon"><x-icon name="clipboard" size="22" /></div>
                        <h3>{{ $totalCatatan }} catatan</h3>
                        <p>Total Notes</p>
                    </div>
                </div>
            
                <!-- Banner Informasi[cite: 7] -->
                <div class="info-banner">
                    <p><strong>!</strong> Data yang ditampilkan merupakan data selama 1 bulan, untuk hasil data mingguan bisa download di page unduh analisis.</p>
                </div>
            
                <!-- Area Chart (Grafik) -->
                <div class="chart-area" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
                    <h3 style="margin-top:0;">Grafik Alat Sering Dipinjam</h3>
                    <canvas id="barangChart" height="80"></canvas>
                </div>
            
                <!-- Tabel Data Peminjaman[cite: 7] -->
                <div class="table-container">
                    <div class="table-header">
                        <span>No</span>
                        <span>Nama</span>
                        <span>NIS</span>
                        <span>Kelas</span>
                        <span>Item</span>
                        <span>Waktu</span>
                    </div>

                    @foreach($transaksis as $index => $trx)
                    <div class="table-row">
                        <span>{{ $index + 1 }}</span>
                        <span>{{ $trx->user->name }}</span>
                        <span>{{ $trx->user->nis }}</span>
                        <span>{{ $trx->user->kelas ?? '-' }}</span>
                        <span>{{ $trx->alat->nama }}</span>
                        <span>{{ \Carbon\Carbon::parse($trx->created_at)->format('D, d-m-Y H:i') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @include('partials.footer')
        </main>

            <!-- Sidebar -->
        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script>
            window.chartLabels = @json($chartLabels);
            window.chartData = @json($chartData);
        </script>
        <script src="{{ asset('js/admindash.js') }}"></script>
    </body>
</html>