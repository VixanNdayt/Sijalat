<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
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
                    <x-user-avatar :user="auth()->user()" />
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            @if (session('success'))
                <div class="flash flash-success flash-actionable">
                    <span>{{ session('success') }}</span>
                    @if (session('struk_id'))
                        <a class="flash-action" href="{{ route('riwayat.struk', session('struk_id')) }}" target="_blank" rel="noopener">
                            <x-icon name="receipt" size="14" /> Lihat Struk
                        </a>
                    @endif
                </div>
            @endif

            @if ($errors->has('hapus'))
                <div class="flash flash-error">{{ $errors->first('hapus') }}</div>
            @endif

            @php
                $formatTanggal = fn ($nilai) => $nilai
                    ? \Carbon\Carbon::parse($nilai)->format('d M Y, H.i')
                    : '-';
                $namaAlat = fn ($pinjam) => $pinjam->alat?->nama ?? 'Alat tidak tersedia';
                $kodeAlat = fn ($pinjam) => $pinjam->alat?->code ?? '-';
            @endphp

            <div class="header-main">
                <div>
                    <h2>Dashboard</h2>
                    <p>Halo, {{ auth()->user()->name }}. Pantau aktivitas peminjaman dan kewajiban anda</p>
                </div>
                <div class="header-actions">
                    <a class="btn-chat-header" href="{{ route('chat.index') }}">
                        <x-icon name="message-circle" size="15" />
                        <span>Chat Admin</span>
                        @if ($unreadChat > 0)
                            <span class="header-badge">{{ $unreadChat > 99 ? '99+' : $unreadChat }}</span>
                        @endif
                    </a>
                    <a href="{{ route('pinjam.create') }}">
                        Ajukan Peminjaman
                    </a>
                </div>
            </div>

            <!-- 4Box -->
            <div class="information-box">
                <article class="info-card">
                    <div class="info-card-head">
                        <span class="info-card-icon"><x-icon name="box" size="18" /></span>
                        <p class="info-card-label">Sedang dipinjam</p>
                    </div>
                    <p class="info-card-value">{{ $sedangDipinjam }}</p>
                    @if($pinjamanAktifTerbaru)
                        <p class="info-card-note">Kembali pada {{ $formatTanggal($pinjamanAktifTerbaru->dikembalikan_saat) }}</p>
                    @else
                        <p class="info-card-note">Belum ada peminjaman aktif</p>
                    @endif
                </article>

                <article class="info-card info-card--warning">
                    <div class="info-card-head">
                        <span class="info-card-icon"><x-icon name="clock" size="18" /></span>
                        <p class="info-card-label">Menunggu persetujuan</p>
                    </div>
                    <p class="info-card-value">{{ $menunggu }}</p>
                    @if($permintaanTerbaru)
                        <p class="info-card-note">Rencana pinjam {{ $formatTanggal($permintaanTerbaru->dipinjam_saat) }}</p>
                    @else
                        <p class="info-card-note">Belum ada permintaan</p>
                    @endif
                </article>

                <article class="info-card info-card--success">
                    <div class="info-card-head">
                        <span class="info-card-icon"><x-icon name="check-circle" size="18" /></span>
                        <p class="info-card-label">Pengembalian selesai</p>
                    </div>
                    <p class="info-card-value">{{ $dikembalikan }}</p>
                    <p class="info-card-note">Total pengembalian selesai</p>
                </article>

                <article class="info-card {{ $pelanggaran > 0 ? 'info-card--danger' : '' }}">
                    <div class="info-card-head">
                        <span class="info-card-icon"><x-icon name="alert-triangle" size="18" /></span>
                        <p class="info-card-label">Catatan pelanggaran</p>
                    </div>
                    <p class="info-card-value">{{ $pelanggaran }}</p>
                    <p class="info-card-note">
                        {{ $pelanggaran > 0
                            ? $pelanggaran . ' peminjaman melewati tenggat'
                            : 'Tidak ada pelanggaran' }}
                    </p>
                </article>
            </div>

            <!-- Barang yang sedang dipinjam -->
            <div class="sedang-pinjam-outer">
                @if ($pinjamanAktif->isNotEmpty())
                    @foreach ( $pinjamanAktif as $item)
                        <div class="sedang-dipinjam">
                            <img src="{{ asset('svg/fluent_box-20-regular.svg') }}" alt="">
                            <div class="text-sd">
                                <div class="inner-text-sd loan-main">
                                    <p class="loan-name">Nama alat : {{ $namaAlat($item) }}</p>
                                    @if ($item->status === 'pending')
                                        <p class="loan-status pending">Pending</p>
                                    @elseif ($item->status === 'dipinjam')
                                        <p class="loan-status dipinjam">Sedang dipinjam</p>
                                    @endif
                                </div>
                                <div class="inner-text-sd loan-dates">
                                    <p>{{ $kodeAlat($item) }}</p>
                                    <p>{{ $formatTanggal($item->dipinjam_saat) }}</p>
                                    <span aria-hidden="true">&rarr;</span>
                                    <p>{{ $formatTanggal($item->dikembalikan_saat) }}</p>
                                </div>
                            </div>

                            @if ($item->status === 'pending')
                                <form action="{{ route('pinjam.destroy', $item->id) }}" method="POST"
                                      class="form-hapus-pinjam"
                                      onsubmit="return confirm(@js('Hapus pengajuan peminjaman ' . $namaAlat($item) . '? Tindakan ini tidak bisa dibatalkan.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-hapus-pinjam" title="Hapus pengajuan">
                                        <x-icon name="trash" size="16" />
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach 
                @endif
            </div>

            <!-- Bottom section -->
            <div class="riwayat">
                <div class="riwayat-peminjaman">
                    <div class="riwayat-head">
                        <div>
                            <h2><x-icon name="clipboard" size="15" /> Riwayat Peminjaman</h2>
                            <p>Aktivitas peminjaman alat kamu.</p>
                        </div>
                        <span class="riwayat-total">{{ $riwayat->total() }} transaksi</span>
                    </div>

                    <div class="rp-table">
                        <div class="rp-row rp-head">
                            <span>Kode</span>
                            <span>Alat</span>
                            <span>Pinjam</span>
                            <span>Kembali</span>
                            <span>Status</span>
                            <span>Aksi</span>
                        </div>

                        @forelse ($riwayat as $item)
                            <div class="rp-row">
                                <span class="rp-cell rp-kode" data-label="Kode">{{ $kodeAlat($item) }}</span>
                                <span class="rp-cell rp-alat" data-label="Alat">{{ $namaAlat($item) }}</span>
                                <span class="rp-cell" data-label="Pinjam">
                                    {{ $formatTanggal($item->dipinjam_saat) }}
                                </span>
                                <span class="rp-cell" data-label="Kembali">
                                    @if ($item->dikembalikan_aktual)
                                        {{ $formatTanggal($item->dikembalikan_aktual) }}
                                    @else
                                        {{ $formatTanggal($item->dikembalikan_saat) }}
                                    @endif
                                </span>
                                <span class="rp-cell rp-status" data-label="Status">
                                    <span class="rp-badge {{ $item->status }}">{{ $item->status_label }}</span>
                                    @if ($item->kondisi_kembali_label)
                                        <span class="rp-badge-kondisi {{ $item->kondisi_kembali }}" @if ($item->catatan_kembali) title="{{ $item->catatan_kembali }}" @endif>{{ $item->kondisi_kembali_label }}</span>
                                        @if ($item->catatan_kembali)
                                            <span class="rp-catatan-kembali">{{ $item->catatan_kembali }}</span>
                                        @endif
                                    @endif
                                </span>
                                <span class="rp-cell rp-aksi" data-label="Aksi">
                                    <span class="rp-aksi-group">
                                        <a class="rp-struk" href="{{ route('riwayat.struk', $item->id) }}" target="_blank" rel="noopener" title="Buka struk transaksi">
                                            <x-icon name="receipt" size="14" />
                                            <span>Struk</span>
                                        </a>
                                        <a class="rp-print" href="{{ route('riwayat.cetak', $item->id) }}" target="_blank" rel="noopener" title="Cetak rincian peminjaman" aria-label="Cetak rincian peminjaman">
                                            <x-icon name="printer" size="14" />
                                            <span class="rp-label">Cetak</span>
                                        </a>
                                    </span>
                                </span>
                            </div>
                        @empty
                            <div class="rp-empty">
                                <x-icon name="inbox" size="18" />
                                <span>Belum ada riwayat peminjaman</span>
                            </div>
                        @endforelse
                    </div>

                    @if ($riwayat->hasPages())
                        <nav class="rp-pagination" aria-label="Navigasi riwayat">
                            @if ($riwayat->onFirstPage())
                                <span class="rp-page disabled">&lsaquo; Sebelumnya</span>
                            @else
                                <a class="rp-page" href="{{ $riwayat->previousPageUrl() }}" rel="prev">&lsaquo; Sebelumnya</a>
                            @endif

                            <span class="rp-page-info">
                                Halaman {{ $riwayat->currentPage() }} dari {{ $riwayat->lastPage() }}
                            </span>

                            @if ($riwayat->hasMorePages())
                                <a class="rp-page" href="{{ $riwayat->nextPageUrl() }}" rel="next">Berikutnya &rsaquo;</a>
                            @else
                                <span class="rp-page disabled">Berikutnya &rsaquo;</span>
                            @endif
                        </nav>
                    @endif
                </div>

                <div class="riwayat-pelanggaran">
                    <div class="riwayat-head">
                        <div>
                            <h2><x-icon name="alert-triangle" size="15" /> Riwayat Pelanggaran</h2>
                            <p>Pengajuan yang melewati batas waktu.</p>
                        </div>
                    </div>

                    <div class="rp-pelanggaran-list">
                        @forelse ($daftarPelanggaran as $item)
                            <div class="rp-pelanggaran-item">
                                <div>
                                    <strong>{{ $namaAlat($item) }}</strong>
                                    <small>
                                        Tenggat {{ $formatTanggal($item->dikembalikan_saat) }}
                                        @if ($item->hari_terlambat > 0)
                                            &middot; terlambat {{ $item->hari_terlambat }} hari
                                        @endif
                                    </small>
                                </div>
                                <span class="rp-badge {{ $item->status }}">
                                    {{ $item->status_label }}
                                </span>
                            </div>
                        @empty
                            <div class="rp-empty">
                                <x-icon name="check-circle" size="18" />
                                <span>Tidak ada pelanggaran tercatat</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @include('partials.footer')
        </main>

        <!-- Sidebar -->
        @include('partials.sidebar-siswa')

        <script src="{{ asset('js/univ.js') }}"></script>
    </body>
</html>