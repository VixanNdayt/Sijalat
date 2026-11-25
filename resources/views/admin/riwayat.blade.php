<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - Riwayat Peminjaman</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}?v=3">
        <link rel="stylesheet" href="{{ asset('css/analisis.css') }}?v=4">
        <link rel="stylesheet" href="{{ asset('css/csp.css') }}">

        @stack('styles')
        @include('partials.theme-head')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <x-user-avatar :user="auth()->user()" />
                    <p>{{ auth()->user()->name }} </p>
                </a>

                @include('partials.brand')
                @include('partials.navbar-actions')
            </nav>

            <div class="analisis-container">
                <div class="header-action">
                    <div>
                        <h2>Riwayat Peminjaman</h2>
                        <p>Pilih rentang tanggal, lalu ekspor data transaksi tanpa menyertakan foto bukti.</p>
                        <p class="filter-note">
                            Periode: {{ $filter['label'] }}
                            ({{ $filter['mulai']->format('d/m/Y') }} - {{ $filter['selesai']->format('d/m/Y') }}).
                        </p>
                        @if ($errors->any())
                            <p class="filter-note u-text-danger">{{ $errors->first() }}</p>
                        @endif
                    </div>

                    <form class="filter-toolbar" method="GET" action="{{ route('admin.riwayat') }}">
                        <div class="filter-fields">
                            <input
                                type="date"
                                class="date-picker"
                                id="riwayatMulai"
                                name="mulai"
                                aria-label="Tanggal mulai"
                                value="{{ $filter['mulai']->format('Y-m-d') }}"
                                required>

                            <input
                                type="date"
                                class="date-picker"
                                id="riwayatSelesai"
                                name="selesai"
                                aria-label="Tanggal selesai"
                                value="{{ $filter['selesai']->format('Y-m-d') }}"
                                required>

                            <button type="submit" class="btn-apply">
                                <x-icon name="search" size="15" /> Terapkan
                            </button>
                        </div>

                        <div class="filter-actions">
                            <span class="filter-actions-label">Ekspor</span>
                            <button
                                type="submit"
                                class="btn-export"
                                formaction="{{ route('admin.riwayat.pdf') }}"
                                aria-label="Ekspor PDF">
                                <x-icon name="file-text" size="15" /> PDF
                            </button>
                            <button
                                type="submit"
                                class="btn-export"
                                formaction="{{ route('admin.riwayat.excel') }}"
                                aria-label="Ekspor Excel">
                                <x-icon name="table" size="15" /> Excel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="grid-4">
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="file-text" size="22" /></span>
                        <div class="data">
                            <p>Total Transaksi</p>
                            <h3>{{ $ringkasan['total'] }}</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="box" size="22" /></span>
                        <div class="data">
                            <p>Total Unit Dipinjam</p>
                            <h3>{{ $ringkasan['unit'] }} unit</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="clock" size="22" /></span>
                        <div class="data">
                            <p>Sedang Dipinjam</p>
                            <h3>{{ $ringkasan['status']['dipinjam'] ?? 0 }}</h3>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <span class="icon"><x-icon name="alert-triangle" size="22" /></span>
                        <div class="data">
                            <p>Terlambat</p>
                            <h3 class="u-text-danger">{{ $ringkasan['terlambat'] }}</h3>
                        </div>
                    </div>
                </div>

                <div class="table-widget">
                    <h4 class="u-flex-center-gap">
                        <x-icon name="clock" size="18" />
                        Daftar Riwayat ({{ $pinjams->total() }} transaksi)
                    </h4>

                    <div class="table-scroll">
                        <table class="data-table riwayat-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Diajukan</th>
                                    <th>Siswa</th>
                                    <th>Alat</th>
                                    <th>Jml</th>
                                    <th>Guru / Mapel</th>
                                    <th>Mulai Pinjam</th>
                                    <th>Tenggat Kembali</th>
                                    <th>Aktual Kembali</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pinjams as $pinjam)
                                    <tr>
                                        <td>{{ $pinjam->kode_pinjam ?: '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($pinjam->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H.i') }}</td>
                                        <td>
                                            <strong>{{ optional($pinjam->user)->name ?? '-' }}</strong>
                                            <div class="riwayat-meta">
                                                {{ optional($pinjam->user)->nis ?: '-' }}
                                                &middot; {{ optional($pinjam->user)->kelas ?: '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            {{ optional($pinjam->alat)->nama ?? '-' }}
                                            <div class="riwayat-meta">
                                                {{ optional($pinjam->alat)->code ?: '-' }}
                                                @if (optional($pinjam->alat)->jenis)
                                                    &middot; {{ \App\Models\Alat::jenisLabel($pinjam->alat->jenis) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ (int) $pinjam->jumlah_minta }}</td>
                                        <td>
                                            {{ optional($pinjam->guru)->nama ?? '-' }}
                                            <div class="riwayat-meta">{{ optional($pinjam->mapel)->nama_mapel ?: '-' }}</div>
                                        </td>
                                        <td>{{ $pinjam->dipinjam_saat ? \Carbon\Carbon::parse($pinjam->dipinjam_saat)->timezone('Asia/Jakarta')->format('d/m/Y H.i') : '-' }}</td>
                                        <td>{{ $pinjam->dikembalikan_saat ? \Carbon\Carbon::parse($pinjam->dikembalikan_saat)->timezone('Asia/Jakarta')->format('d/m/Y H.i') : '-' }}</td>
                                        <td>{{ $pinjam->dikembalikan_aktual ? \Carbon\Carbon::parse($pinjam->dikembalikan_aktual)->timezone('Asia/Jakarta')->format('d/m/Y H.i') : '-' }}</td>
                                        <td>
                                            <span class="badge {{ $pinjam->status }}">{{ $pinjam->status_short_label }}</span>
                                            @if ($pinjam->terlambat_label)
                                                <div class="riwayat-meta u-text-danger">{{ $pinjam->terlambat_label }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="table-empty">Tidak ada riwayat peminjaman pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pinjams->hasPages())
                        <div class="table-pager">
                            @if ($pinjams->onFirstPage())
                                <span class="pager-link is-disabled">Sebelumnya</span>
                            @else
                                <a class="pager-link" href="{{ $pinjams->previousPageUrl() }}">Sebelumnya</a>
                            @endif

                            <span class="pager-info">
                                Halaman {{ $pinjams->currentPage() }} dari {{ $pinjams->lastPage() }}
                            </span>

                            @if ($pinjams->hasMorePages())
                                <a class="pager-link" href="{{ $pinjams->nextPageUrl() }}">Berikutnya</a>
                            @else
                                <span class="pager-link is-disabled">Berikutnya</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @include('partials.footer')
        </main>

        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/manage.js') }}"></script>
    </body>
</html>
