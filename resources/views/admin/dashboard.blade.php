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
            
                <!-- 4 Kotak Ringkasan Data -->
                <div class="card-grid">
                    <div class="summary-card tone-blue">
                        <span class="icon"><x-icon name="trending-up" size="20" /></span>
                        <div class="summary-body">
                            <p class="summary-value">{{ $totalMasuk }}</p>
                            <p class="summary-label">Total data masuk</p>
                        </div>
                    </div>

                    <div class="summary-card tone-green">
                        <span class="icon"><x-icon name="check-circle" size="20" /></span>
                        <div class="summary-body">
                            <p class="summary-value">{{ $totalApproved }}</p>
                            <p class="summary-label">Disetujui</p>
                        </div>
                    </div>

                    <div class="summary-card tone-amber">
                        <span class="icon"><x-icon name="clock" size="20" /></span>
                        <div class="summary-body">
                            <p class="summary-value">{{ $totalPending }}</p>
                            <p class="summary-label">Menunggu persetujuan</p>
                        </div>
                    </div>

                    <div class="summary-card tone-red">
                        <span class="icon"><x-icon name="clipboard" size="20" /></span>
                        <div class="summary-body">
                            <p class="summary-value">{{ $totalCatatan }}</p>
                            <p class="summary-label">Catatan pelanggaran</p>
                        </div>
                    </div>
                </div>
            
                <!-- Banner Informasi -->
                <div class="info-banner">
                    <p><strong>!</strong> Data yang ditampilkan merupakan data selama 1 bulan. Untuk hasil data mingguan, unduh melalui halaman analisis.</p>
                </div>
            
                <!-- Area Chart (Grafik) -->
                <div class="chart-area">
                    <h3>Grafik Alat Sering Dipinjam</h3>
                    @if ($chartLabels->isEmpty())
                        <p class="chart-empty">Belum ada data peminjaman pada periode ini.</p>
                    @else
                        <canvas id="barangChart" height="80"></canvas>
                    @endif
                </div>
            
                <!-- Tabel Data Peminjaman -->
                <div class="table-container">
                    <div class="table-header">
                        <span>No</span>
                        <span>Nama</span>
                        <span>NIS</span>
                        <span>Kelas</span>
                        <span>Item</span>
                        <span>Waktu</span>
                    </div>

                    @forelse($transaksis as $index => $trx)
                        <div class="table-row">
                            <span>{{ $index + 1 }}</span>
                            <span>{{ $trx->user?->name ?? 'Pengguna tidak tersedia' }}</span>
                            <span>{{ $trx->user?->nis ?? '-' }}</span>
                            <span>{{ $trx->user?->kelas ?? '-' }}</span>
                            <span>{{ $trx->alat?->nama ?? 'Alat tidak tersedia' }}</span>
                            <span>{{ $trx->created_at ? \Carbon\Carbon::parse($trx->created_at)->format('D, d-m-Y H:i') : '-' }}</span>
                        </div>
                    @empty
                        <div class="table-empty">Belum ada data peminjaman.</div>
                    @endforelse
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