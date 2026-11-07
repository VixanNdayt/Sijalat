<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
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
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <div class="analisis-container">
                @php
                    $tanggalLabel = $periode === 'semua' ? null : $periodeLabel;
                    $filterParams = array_filter(
                        ['periode' => $periode, 'tanggal' => $tanggal],
                        fn ($nilai) => $nilai !== null && $nilai !== ''
                    );
                @endphp
                <!-- HEADER & KONTROL -->
                <div class="header-action">
                    <div>
                        <h2>Dashboard Analisis & Laporan</h2>
                        <p>Pantau metrik inventaris dan aktivitas lab secara real-time.</p>
                        @if ($periode !== 'semua')
                            <p class="filter-note">
                                Periode: {{ $periodeLabel }}
                                ({{ $mulai->format('d/m/Y') }} - {{ $selesai->format('d/m/Y') }}).
                                <a href="{{ route('admin.analisis') }}">Tampilkan semua data</a>
                            </p>
                        @endif
                    </div>
                    <div class="kontrol-filter">
                        <select id="filterPeriode" class="date-picker" aria-label="Pilih periode laporan">
                            <option value="semua" @selected($periode === 'semua')>Semua Data</option>
                            <option value="harian" @selected($periode === 'harian')>Harian</option>
                            <option value="mingguan" @selected($periode === 'mingguan')>Mingguan</option>
                            <option value="bulanan" @selected($periode === 'bulanan')>Bulanan</option>
                            <option value="semester" @selected($periode === 'semester')>Satu Semester</option>
                        </select>
                        <input type="date" class="date-picker" id="filterTanggal" value="{{ $tanggal }}" aria-label="Tanggal acuan periode" @disabled($periode === 'semua')>
                        <a class="btn-export pdf" href="{{ route('admin.analisis.pdf', $filterParams) }}">
                            <x-icon name="file-text" size="15" /> Export PDF
                        </a>
                        <a class="btn-export excel" href="{{ route('admin.analisis.excel', $filterParams) }}">
                            <x-icon name="table" size="15" /> Export Excel
                        </a>
                    </div>
                </div>
            
                <!-- 1. METRIC CARDS (4 Kolom) -->
                <div class="grid-4">
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="box" size="22" /></span>
                        <div class="data">
                            <p>Total Inventaris</p>
                            <h3>{{ $totalSKU }} SKU / {{ $totalUnit }} Unit</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="refresh" size="22" /></span>
                        <div class="data">
                            <p>Distribusi Stok</p>
                            <h3>{{ $stokTersedia }} Ready / {{ $sedangDipinjam }} Out</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="wrench" size="22" /></span>
                        <div class="data">
                            <p>Kondisi Fisik</p>
                            <h3 style="font-size: 13px;">B: {{ $kondisiBaik }} | C: {{ $kondisiPengecekan }} | R: {{ $kondisiRusak }} | M: {{ $kondisiMtc }}</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="alert-triangle" size="22" /></span>
                        <div class="data">
                            <p>Status Transaksi</p>
                            <h3 style="font-size: 14px;">{{ $pendingApproval }} Pend | {{ $activeLoans }} Act | <span style="color:red;">{{ $overdueCount }} Over</span></h3>
                        </div>
                    </div>
                </div>
            
                <!-- 2. GRAFIK VISUALISASI (2 Kolom) -->
                <div class="grid-2">
                    <div class="chart-box">
                        <h4>Tren Peminjaman ({{ $tanggalLabel ?: '7 Hari Terakhir' }})</h4>
                        <canvas id="trendChart" height="100"></canvas>
                    </div>
                    <div class="chart-box">
                        <h4>Top 5 Barang Sering Dipinjam{{ $tanggalLabel ? ' - '.$tanggalLabel : '' }}</h4>
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
                        <h4 style="color: #dc2626; display:flex; align-items:center; gap:6px;"><x-icon name="alert-circle" size="18" /> Alert Stok &amp; Kerusakan</h4>
                        <table class="data-table">
                            <tr>
                                <th>Nama Alat</th>
                                <th>Stok / Baik</th>
                                <th>Cek / Rusak</th>
                                <th>Mtc</th>
                            </tr>
                            @foreach($stockAlerts as $alert)
                            <tr>
                                <td>{{ $alert->nama }}</td>
                                <td>{{ $alert->jumlah_stok }} / {{ $alert->kondisi_baik }}</td>
                                <td><span style="color:#0369a1;">{{ $alert->kondisi_pengecekan }}</span> / <span style="color:#dc2626;">{{ $alert->kondisi_rusak }}</span></td>
                                <td>{{ $alert->kondisi_maintenance }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                
                    <!-- Tabel Recent Activity -->
                    <div class="table-widget">
                        <h4 style="display:flex; align-items:center; gap:6px;"><x-icon name="clock" size="18" /> Log Aktivitas {{ $tanggalLabel ? $tanggalLabel : 'Terbaru' }}</h4>
                        <table class="data-table">
                            <tr>
                                <th>Waktu</th>
                                <th>Siswa</th>
                                <th>Barang</th>
                                <th>Status</th>
                            </tr>
                            @forelse($recentActivities as $recent)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($recent->created_at)->format('d/m, H:i') }}</td>
                                <td>{{ optional($recent->user)->name ?? '-' }}</td>
                                <td>{{ optional($recent->alat)->nama ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $recent->status }}">{{ $recent->status_label }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="table-empty">Tidak ada aktivitas pada periode ini.</td>
                            </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        
            @include('partials.footer')
        
        </main>

        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/manage.js') }}"></script>
        <script>
            // Data dari Controller
            const topLabels = @json($topItems->pluck('nama'));
            const topData = @json($topItems->pluck('total_pinjam'));
            
            const trendLabels = @json($trenHarian->pluck('label'));
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
                    labels: ['Baik', 'Butuh Pengecekan', 'Rusak / Perlu Service', 'Maintenance'],
                    datasets: [{
                        data: [{{ $kondisiBaik }}, {{ $kondisiPengecekan }}, {{ $kondisiRusak }}, {{ $kondisiMtc }}],
                        backgroundColor: ['#10b981', '#3b82f6', '#ef4444', '#f59e0b']
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

            // Filter periode & tanggal acuan
            const filterPeriode = document.getElementById('filterPeriode');
            const filterTanggal = document.getElementById('filterTanggal');

            function terapkanFilter() {
                const url = new URL(window.location.href);
                const periode = filterPeriode ? filterPeriode.value : 'semua';

                url.searchParams.set('periode', periode);

                if (periode === 'semua') {
                    url.searchParams.delete('tanggal');
                } else if (filterTanggal && filterTanggal.value) {
                    url.searchParams.set('tanggal', filterTanggal.value);
                }

                window.location.href = url.toString();
            }

            if (filterPeriode) {
                filterPeriode.addEventListener('change', function () {
                    if (filterTanggal) {
                        filterTanggal.disabled = this.value === 'semua';
                    }

                    terapkanFilter();
                });
            }

            if (filterTanggal) {
                filterTanggal.addEventListener('change', terapkanFilter);
            }
        </script>
    </body>
</html>

