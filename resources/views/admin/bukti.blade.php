<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - Bukti Transaksi</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin-bukti.css') }}">

        @stack('styles')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="">
                    <p>{{ auth()->user()->name }}</p>
                </a>

                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <div class="bukti-container">
                <div class="bukti-header">
                    <div>
                        <h2>Bukti Pengambilan &amp; Pengembalian</h2>
                        <p>Dokumentasi foto yang diunggah siswa saat mengambil dan mengembalikan alat di laboratorium.</p>
                    </div>
                </div>

                @php
                    $formatWaktu = fn ($nilai, $format = 'd M Y, H.i') => $nilai
                        ? \Carbon\Carbon::parse($nilai)->format($format)
                        : '-';

                    $tabItems = [
                        'semua' => ['label' => 'Semua', 'jumlah' => $ringkasan['transaksi']],
                        'ambil' => ['label' => 'Pengambilan', 'jumlah' => $ringkasan['ambil']],
                        'kembali' => ['label' => 'Pengembalian', 'jumlah' => $ringkasan['kembali']],
                    ];
                @endphp

                <div class="bukti-stats" aria-label="Ringkasan bukti transaksi">
                    <div class="bukti-stat is-transaksi">
                        <span class="bukti-stat-icon"><x-icon name="receipt" size="18" /></span>
                        <div>
                            <span>Transaksi</span>
                            <strong>{{ $ringkasan['transaksi'] }}</strong>
                        </div>
                    </div>
                    <div class="bukti-stat is-ambil">
                        <span class="bukti-stat-icon"><x-icon name="camera" size="18" /></span>
                        <div>
                            <span>Bukti ambil</span>
                            <strong>{{ $ringkasan['ambil'] }}</strong>
                        </div>
                    </div>
                    <div class="bukti-stat is-kembali">
                        <span class="bukti-stat-icon"><x-icon name="refresh" size="18" /></span>
                        <div>
                            <span>Bukti kembali</span>
                            <strong>{{ $ringkasan['kembali'] }}</strong>
                        </div>
                    </div>
                    <div class="bukti-stat is-lengkap">
                        <span class="bukti-stat-icon"><x-icon name="check-circle" size="18" /></span>
                        <div>
                            <span>Lengkap</span>
                            <strong>{{ $ringkasan['lengkap'] }}</strong>
                        </div>
                    </div>
                </div>

                <div class="bukti-toolbar">
                    <div class="bukti-tabs" role="tablist" aria-label="Filter jenis bukti">
                        @foreach ($tabItems as $key => $tab)
                            <a href="{{ route('admin.bukti.index', array_filter(['jenis' => $key === 'semua' ? null : $key, 'cari' => $cari !== '' ? $cari : null])) }}"
                               class="bukti-tab {{ $jenis === $key ? 'active' : '' }}"
                               @if ($jenis === $key) aria-current="page" @endif>
                                {{ $tab['label'] }}
                                <span class="tab-count">{{ $tab['jumlah'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    <form class="bukti-search" method="GET" action="{{ route('admin.bukti.index') }}" role="search">
                        @if ($jenis !== 'semua')
                            <input type="hidden" name="jenis" value="{{ $jenis }}">
                        @endif

                        <div class="bukti-search-field">
                            <x-icon name="search" size="16" />
                            <input type="search" name="cari" value="{{ $cari }}"
                                   placeholder="Cari nama, NIS, kode, atau alat..."
                                   aria-label="Cari bukti transaksi" autocomplete="off">
                            @if ($cari !== '')
                                <a href="{{ route('admin.bukti.index', $jenis !== 'semua' ? ['jenis' => $jenis] : []) }}"
                                   class="bukti-search-reset" aria-label="Hapus pencarian">
                                    <x-icon name="x" size="14" />
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="bukti-search-btn">Cari</button>
                    </form>
                </div>

                <div class="bukti-grid">
                    @forelse ($pinjams as $pinjam)
                        @php
                            $fotoAmbil = $pinjam->file_ambil
                                ? route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'ambil'])
                                : null;
                            $fotoKembali = $pinjam->file_kembali
                                ? route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'kembali'])
                                : null;

                            $namaSiswa = $pinjam->user?->name ?? 'Pengguna tidak tersedia';
                            $namaAlat = $pinjam->alat?->nama ?? 'Alat tidak tersedia';
                            $kondisi = $pinjam->kondisi_kembali;
                        @endphp

                        <article class="bukti-card">
                            <header class="bukti-card-head">
                                <div>
                                    <h3>{{ $namaSiswa }}</h3>
                                    <p>
                                        NIS {{ $pinjam->user?->nis ?? '-' }}
                                        &middot; {{ $pinjam->user?->kelas ?: 'Kelas belum diisi' }}
                                    </p>
                                </div>
                                <span class="status-pill {{ $pinjam->status }}">{{ $pinjam->status_label }}</span>
                            </header>

                            <dl class="bukti-meta">
                                <div>
                                    <dt>Alat</dt>
                                    <dd>{{ $namaAlat }}</dd>
                                </div>
                                <div>
                                    <dt>Jumlah</dt>
                                    <dd>{{ $pinjam->jumlah_minta }} unit</dd>
                                </div>
                                <div>
                                    <dt>Kode pinjam</dt>
                                    <dd class="mono">{{ $pinjam->kode_pinjam ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Ruangan</dt>
                                    <dd>{{ $pinjam->ruangan ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Jadwal pinjam</dt>
                                    <dd>{{ $formatWaktu($pinjam->dipinjam_saat) }}</dd>
                                </div>
                                <div>
                                    <dt>Dikembalikan</dt>
                                    <dd>{{ $formatWaktu($pinjam->dikembalikan_aktual) }}</dd>
                                </div>
                            </dl>

                            <div class="bukti-proofs">
                                <div class="proof-block">
                                    <div class="proof-head">
                                        <span class="proof-label"><x-icon name="camera" size="14" /> Pengambilan</span>
                                        <span class="proof-time">Jadwal {{ $formatWaktu($pinjam->dipinjam_saat, 'd M') }}</span>
                                    </div>

                                    @if ($fotoAmbil)
                                        <button type="button" class="proof-thumb"
                                                data-foto="{{ $fotoAmbil }}"
                                                data-kicker="{{ $namaSiswa }} &middot; {{ $pinjam->kode_pinjam ?: '-' }}"
                                                data-caption="Bukti pengambilan &middot; {{ $namaAlat }}"
                                                aria-label="Perbesar bukti pengambilan {{ $namaAlat }}">
                                            <img src="{{ $fotoAmbil }}" alt="Bukti pengambilan {{ $namaAlat }}" loading="lazy" decoding="async">
                                            <span class="proof-zoom"><x-icon name="search" size="13" /> Lihat</span>
                                        </button>
                                    @elseif ($pinjam->bukti_ambil)
                                        <div class="proof-empty is-missing">
                                            <x-icon name="alert-triangle" size="22" />
                                            <span>File bukti tidak ditemukan</span>
                                        </div>
                                    @else
                                        <div class="proof-empty">
                                            <x-icon name="camera" size="22" />
                                            <span>Belum ada bukti</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="proof-block">
                                    <div class="proof-head">
                                        <span class="proof-label"><x-icon name="refresh" size="14" /> Pengembalian</span>
                                        <span class="proof-time">{{ $formatWaktu($pinjam->dikembalikan_aktual, 'd M') }}</span>
                                    </div>

                                    @if ($fotoKembali)
                                        <button type="button" class="proof-thumb"
                                                data-foto="{{ $fotoKembali }}"
                                                data-kicker="{{ $namaSiswa }} &middot; {{ $pinjam->kode_pinjam ?: '-' }}"
                                                data-caption="Bukti pengembalian &middot; {{ $namaAlat }}"
                                                aria-label="Perbesar bukti pengembalian {{ $namaAlat }}">
                                            <img src="{{ $fotoKembali }}" alt="Bukti pengembalian {{ $namaAlat }}" loading="lazy" decoding="async">
                                            <span class="proof-zoom"><x-icon name="search" size="13" /> Lihat</span>
                                        </button>
                                    @elseif ($pinjam->bukti_kembali)
                                        <div class="proof-empty is-missing">
                                            <x-icon name="alert-triangle" size="22" />
                                            <span>File bukti tidak ditemukan</span>
                                        </div>
                                    @else
                                        <div class="proof-empty">
                                            <x-icon name="refresh" size="22" />
                                            <span>Belum ada bukti</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($kondisi || $pinjam->catatan_kembali)
                                <div class="bukti-return {{ $kondisi ? 'is-' . $kondisi : '' }}">
                                    <div class="return-head">
                                        <span>Kondisi saat kembali</span>
                                        <span class="condition-pill {{ $kondisi }}">{{ $pinjam->kondisi_kembali_label ?? 'Tidak dicatat' }}</span>
                                    </div>
                                    @if ($pinjam->catatan_kembali)
                                        <p>{{ $pinjam->catatan_kembali }}</p>
                                    @endif
                                </div>
                            @endif

                            <footer class="bukti-card-foot">
                                <span><x-icon name="clock" size="13" /> Diperbarui {{ $pinjam->updated_at?->format('d M Y, H.i') }}</span>
                                <span class="proof-count">
                                    {{ ($pinjam->file_ambil ? 1 : 0) + ($pinjam->file_kembali ? 1 : 0) }} bukti tersedia
                                </span>
                            </footer>
                        </article>
                    @empty
                        <div class="bukti-empty">
                            <span class="bukti-empty-icon"><x-icon name="camera" size="30" /></span>
                            <h3>Belum ada bukti transaksi</h3>
                            <p>
                                @if ($cari !== '' || $jenis !== 'semua')
                                    Tidak ada bukti yang cocok dengan filter atau pencarian saat ini.
                                @else
                                    Bukti foto akan muncul di sini setelah siswa menyelesaikan pengambilan atau pengembalian alat.
                                @endif
                            </p>
                            @if ($cari !== '' || $jenis !== 'semua')
                                <a href="{{ route('admin.bukti.index') }}" class="btn-secondary">Tampilkan semua bukti</a>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if ($pinjams->hasPages())
                    <p class="bukti-summary">
                        Menampilkan {{ $pinjams->firstItem() }}&ndash;{{ $pinjams->lastItem() }}
                        dari {{ $pinjams->total() }} transaksi
                    </p>

                    <nav class="bukti-pagination" aria-label="Navigasi halaman bukti">
                        @if ($pinjams->onFirstPage())
                            <span class="page-link disabled">&lsaquo; Sebelumnya</span>
                        @else
                            <a href="{{ $pinjams->previousPageUrl() }}" class="page-link">&lsaquo; Sebelumnya</a>
                        @endif

                        @foreach ($pinjams->getUrlRange(max(1, $pinjams->currentPage() - 2), min($pinjams->lastPage(), $pinjams->currentPage() + 2)) as $halaman => $url)
                            @if ($halaman === $pinjams->currentPage())
                                <span class="page-link active" aria-current="page">{{ $halaman }}</span>
                            @else
                                <a href="{{ $url }}" class="page-link">{{ $halaman }}</a>
                            @endif
                        @endforeach

                        @if ($pinjams->hasMorePages())
                            <a href="{{ $pinjams->nextPageUrl() }}" class="page-link">Berikutnya &rsaquo;</a>
                        @else
                            <span class="page-link disabled">Berikutnya &rsaquo;</span>
                        @endif
                    </nav>
                @endif
            </div>

            @include('partials.footer')
        </main>

        @include('partials.sidebar-admin')

        <div class="proof-lightbox" id="proofLightbox" hidden aria-hidden="true">
            <div class="proof-lightbox-backdrop" data-lightbox-close></div>

            <div class="proof-lightbox-dialog" role="dialog" aria-modal="true" aria-labelledby="proofLightboxCaption">
                <header class="proof-lightbox-head">
                    <div>
                        <p class="proof-lightbox-kicker" id="proofLightboxKicker"></p>
                        <h3 id="proofLightboxCaption"></h3>
                    </div>
                    <button type="button" class="proof-lightbox-close" data-lightbox-close aria-label="Tutup pratinjau">
                        <x-icon name="x" size="20" />
                    </button>
                </header>

                <div class="proof-lightbox-body">
                    <button type="button" class="proof-lightbox-nav prev" data-lightbox-prev aria-label="Bukti sebelumnya">
                        <x-icon name="arrow-left" size="20" />
                    </button>

                    <img id="proofLightboxImage" alt="">

                    <button type="button" class="proof-lightbox-nav next" data-lightbox-next aria-label="Bukti berikutnya">
                        <x-icon name="arrow-left" size="20" />
                    </button>
                </div>

                <footer class="proof-lightbox-foot">
                    <span id="proofLightboxCounter"></span>
                    <a id="proofLightboxOpen" href="#" target="_blank" rel="noopener">Buka di tab baru</a>
                </footer>
            </div>
        </div>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/admin-bukti.js') }}"></script>
    </body>
</html>
