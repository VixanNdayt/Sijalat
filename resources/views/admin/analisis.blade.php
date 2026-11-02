<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/analisis.css') }}">

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

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <div class="analisis-container">
                <!-- HEADER & KONTROL -->
                <div class="header-action">
                    <div>
                        <h2>Dashboard Analisis & Laporan</h2>
                        <p>Pantau metrik inventaris dan aktivitas lab secara real-time.</p>
                    </div>
                    <div class="kontrol-filter">
                        <input type="date" class="date-picker">
                        <button class="btn-export pdf">📄 Export PDF</button>
                        <button class="btn-export excel">📊 Export Excel</button>
                    </div>
                </div>
            
                <!-- 1. METRIC CARDS (4 Kolom) -->
                <div class="grid-4">
                    <div class="kpi-card">
                        <span class="icon">📦</span>
                        <div class="data">
                            <p>Total Inventaris</p>
                            <h3>{{ $totalSKU }} SKU / {{ $totalUnit }} Unit</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon">🔄</span>
                        <div class="data">
                            <p>Distribusi Stok</p>
                            <h3>{{ $stokTersedia }} Ready / {{ $sedangDipinjam }} Out</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon">🛠️</span>
                        <div class="data">
                            <p>Kondisi Fisik</p>
                            <h3 style="font-size: 14px;">B: {{ $kondisiBaik }} | R: {{ $kondisiRusak }} | M: {{ $kondisiMtc }}</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon">⚠️</span>
                        <div class="data">
                            <p>Status Transaksi</p>
                            <h3 style="font-size: 14px;">{{ $pendingApproval }} Pend | {{ $activeLoans }} Act | <span style="color:red;">{{ $overdueCount }} Over</span></h3>
                        </div>
                    </div>
                </div>
            
                <!-- 2. GRAFIK VISUALISASI (2 Kolom) -->
                <div class="grid-2">
                    <div class="chart-box">
                        <h4>Tren Peminjaman (7 Hari Terakhir)</h4>
                        <canvas id="trendChart" height="100"></canvas>
                    </div>
                    <div class="chart-box">
                        <h4>Top 5 Barang Sering Dipinjam</h4>
                        <canvas id="topItemChart" height="100"></canvas>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="chart-box">
                        <h4>Persentase Kondisi Fisik</h4>
                        <canvas id="kondisiChart" height="100"></canvas>
                    </div>
                    <div class="chart-box">
                        <h4>Proporsi Stok (Tersedia vs Dipinjam)</h4>
                        <canvas id="distribusiChart" height="100"></canvas>
                    </div>
                </div>
            
                <!-- 3. TABEL MONITORING (2 Kolom) -->
                <div class="grid-2">
                    <!-- Tabel Alert Stok & Perbaikan -->
                    <div class="table-widget">
                        <h4 style="color: #dc2626;">🚨 Alert Stok & Kerusakan</h4>
                        <table class="data-table">
                            <tr>
                                <th>Nama Alat</th>
                                <th>Stok / Baik</th>
                                <th>Rusak / Mtc</th>
                            </tr>
                            @foreach($stockAlerts as $alert)
                            <tr>
                                <td>{{ $alert->nama }}</td>
                                <td>{{ $alert->jumlah_stok }} / {{ $alert->kondisi_baik }}</td>
                                <td style="color:red;">{{ $alert->kondisi_rusak }} / {{ $alert->kondisi_maintenance }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                
                    <!-- Tabel Recent Activity -->
                    <div class="table-widget">
                        <h4>🕒 Log Aktivitas Terbaru</h4>
                        <table class="data-table">
                            <tr>
                                <th>Waktu</th>
                                <th>Siswa</th>
                                <th>Barang</th>
                                <th>Status</th>
                            </tr>
                            @foreach($recentActivities as $recent)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($recent->created_at)->format('d/m, H:i') }}</td>
                                <td>{{ $recent->user->name }}</td>
                                <td>{{ $recent->alat->nama }}</td>
                                <td>
                                    <span class="badge {{ $recent->status }}">{{ strtoupper($recent->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        
        </main>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('approval.index') }}">Approval</a>
                <a href="{{ route('admin.alat.index') }}">Manage Data</a>
                <a href="{{ route('admin.analisis') }}">Analisis</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/manage.js') }}"></script>
        <script>
            // Data dari Controller
            const topLabels = @json($topItems->pluck('nama'));
            const topData = @json($topItems->pluck('total_pinjam'));
            
            const trendLabels = @json($trenHarian->pluck('date'));
            const trendData = @json($trenHarian->pluck('total'));

            // 1. Line Chart (Tren Peminjaman)
            new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Transaksi',
                        data: trendData,
                        borderColor: '#3b82f6',
                        tension: 0.3, // Membuat garis sedikit melengkung
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)'
                    }]
                }
            });

            // 2. Horizontal Bar Chart (Top 5 Barang)
            new Chart(document.getElementById('topItemChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topLabels,
                    datasets: [{
                        label: 'Total Dipinjam',
                        data: topData,
                        backgroundColor: '#10b981'
                    }]
                },
                options: { indexAxis: 'y' } // Mengubah bar menjadi horizontal
            });

            // 3. Doughnut Chart (Kondisi Fisik)
            new Chart(document.getElementById('kondisiChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Baik', 'Rusak / Perlu Service', 'Maintenance'],
                    datasets: [{
                        data: [{{ $kondisiBaik }}, {{ $kondisiRusak }}, {{ $kondisiMtc }}],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
                    }]
                }
            });

            // 4. Pie Chart (Stok Out vs In)
            new Chart(document.getElementById('distribusiChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Stok Tersedia (Gudang)', 'Sedang Dipinjam (Luar)'],
                    datasets: [{
                        data: [{{ $stokTersedia }}, {{ $sedangDipinjam }}],
                        backgroundColor: ['#3b82f6', '#8b5cf6']
                    }]
                }
            });
        </script>
    </body>
</html>

