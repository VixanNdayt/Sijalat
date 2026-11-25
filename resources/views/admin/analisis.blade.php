<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}?v=3">
        <link rel="stylesheet" href="{{ asset('css/analisis.css') }}?v=4">
        <link rel="stylesheet" href="{{ asset('css/csp.css') }}">

        @stack('styles')
        @include('partials.theme-head')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <x-user-avatar :user="auth()->user()" />
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                @include('partials.brand')
                @include('partials.navbar-actions')
            </nav>

            <script src="{{ asset('vendor/chart.umd.min.js') }}"></script>

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
                    <div class="filter-toolbar">
                        <div class="filter-fields">
                            <select id="filterPeriode" class="date-picker" aria-label="Pilih periode laporan" data-custom-select>
                                <option value="semua" @selected($periode === 'semua')>Semua Data</option>
                                <option value="harian" @selected($periode === 'harian')>Harian</option>
                                <option value="mingguan" @selected($periode === 'mingguan')>Mingguan</option>
                                <option value="bulanan" @selected($periode === 'bulanan')>Bulanan</option>
                                <option value="semester" @selected($periode === 'semester')>Satu Semester</option>
                            </select>
                            <input type="date" class="date-picker" id="filterTanggal" value="{{ $tanggal }}" aria-label="Tanggal acuan periode" @disabled($periode === 'semua')>
                        </div>

                        <div class="filter-actions">
                            <span class="filter-actions-label">Ekspor</span>
                            <a class="btn-export" href="{{ route('admin.analisis.pdf', $filterParams) }}" aria-label="Ekspor PDF">
                                <x-icon name="file-text" size="15" /> PDF
                            </a>
                            <a class="btn-export" href="{{ route('admin.analisis.excel', $filterParams) }}" aria-label="Ekspor Excel">
                                <x-icon name="table" size="15" /> Excel
                            </a>
                        </div>
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
                            <h3 class="u-fs-13">B: {{ $kondisiBaik }} | C: {{ $kondisiPengecekan }} | R: {{ $kondisiRusak }} | M: {{ $kondisiMtc }}</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="alert-triangle" size="22" /></span>
                        <div class="data">
                            <p>Status Transaksi</p>
                            <h3 class="u-fs-14">{{ $pendingApproval }} Pend | {{ $activeLoans }} Act | <span class="u-text-danger">{{ $overdueCount }} Over</span></h3>
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
                        <h4 class="u-flex-center-gap u-text-danger"><x-icon name="alert-circle" size="18" /> Alert Stok &amp; Kerusakan</h4>
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
                                <td><span class="u-text-info">{{ $alert->kondisi_pengecekan }}</span> / <span class="u-text-danger">{{ $alert->kondisi_rusak }}</span></td>
                                <td>{{ $alert->kondisi_maintenance }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                
                    <!-- Tabel Recent Activity -->
                    <div class="table-widget">
                        <h4 class="u-flex-center-gap"><x-icon name="clock" size="18" /> Log Aktivitas {{ $tanggalLabel ? $tanggalLabel : 'Terbaru' }}</h4>
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
        <script src="{{ asset('js/custom-select.js') }}?v=4"></script>
        <script type="application/json" id="analisis-data">
{!! Illuminate\Support\Js::encode([
    'topLabels' => $topItems->pluck('nama')->values(),
    'topData' => $topItems->pluck('total_pinjam')->values(),
    'trendLabels' => $trenHarian->pluck('label')->values(),
    'trendData' => $trenHarian->pluck('total')->values(),
    'kondisiBaik' => (int) $kondisiBaik,
    'kondisiPengecekan' => (int) $kondisiPengecekan,
    'kondisiRusak' => (int) $kondisiRusak,
    'kondisiMtc' => (int) $kondisiMtc,
    'stokTersedia' => (int) $stokTersedia,
    'sedangDipinjam' => (int) $sedangDipinjam,
]) !!}
        </script>
        <script src="{{ asset('js/analisis.js') }}?v=2"></script>
    </body>
</html>

