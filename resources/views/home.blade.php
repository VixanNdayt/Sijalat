<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="SIJALAT - Sistem Informasi Peminjaman Alat & Inventaris Jurusan. Cari alat praktikum, cek ketersediaan, dan ajukan peminjaman dalam satu tempat.">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT &middot; Peminjaman Alat &amp; Inventaris Jurusan</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/home.css') }}">
        @stack('styles')
    </head>
    <body class="home-page">
        <a class="home-skip" href="#katalog">Lewati ke katalog alat</a>

        <header class="home-header" id="atas">
            <div class="home-header-inner">
                <a class="home-brand" href="{{ route('home') }}" aria-label="SIJALAT - Beranda">
                    <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="" width="38" height="38">
                    <span>SIJALAT</span>
                </a>

                <nav class="home-nav-links" id="homeNavLinks" aria-label="Navigasi utama">
                    <a href="#katalog">Katalog</a>
                    <a href="#alur">Alur Peminjaman</a>
                    <a href="#regulasi">Regulasi</a>
                    <a href="#aktivitas">Aktivitas</a>
                    <a href="#kontak">Kontak</a>
                </nav>

                <div class="home-auth">
                    @auth
                        <details class="home-user">
                            <summary>
                                <x-user-avatar :user="auth()->user()" />
                                <span class="home-user-name">{{ auth()->user()->name }}</span>
                                <x-icon name="chevron-down" size="14" />
                            </summary>
                            <div class="home-user-menu">
                                <a href="{{ route('dashboard') }}">
                                    <x-icon name="layers" size="15" /> Dashboard
                                </a>
                                <a href="{{ route('profile.edit') }}">
                                    <x-icon name="user" size="15" /> Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">
                                        <x-icon name="log-out" size="15" /> Keluar
                                    </button>
                                </form>
                            </div>
                        </details>
                    @else
                        <a class="home-login" href="{{ route('login') }}">Masuk</a>
                        <a class="home-btn home-btn-accent home-btn-sm" href="{{ route('register') }}">Daftar</a>
                    @endauth

                    <button type="button" class="home-menu-toggle" id="homeMenuToggle"
                            aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="homeNavLinks">
                        <x-icon name="menu" size="19" />
                    </button>
                </div>
            </div>
        </header>

        <main class="home-main">
            <!-- ================================================
                 1. HERO & PENCARIAN CEPAT
                 ================================================ -->
            <section class="home-hero">
                <div class="home-hero-inner">
                    <div class="home-hero-copy">
                        <span class="home-badge">
                            <span class="home-badge-dot"></span>
                            Inventaris jurusan, dalam satu tempat
                        </span>

                        <h1>Sistem Informasi Peminjaman Alat &amp; <em>Inventaris Jurusan</em></h1>

                        <p class="home-hero-sub">
                            Fokus pada pelayanan praktikum dan kegiatan pembelajaran. Cari alat,
                            cek ketersediaan real-time, ajukan pinjaman, lalu tunjukkan QR
                            pengambilan saat serah terima di ruang alat.
                        </p>

                        <form class="home-hero-search" method="GET" action="{{ route('home') }}#katalog" role="search">
                            <div class="home-search-field">
                                <x-icon name="search" size="18" />
                                <input type="search" name="q" value="{{ $q }}"
                                       placeholder="Cari nama alat, kategori, atau kode inventaris..."
                                       aria-label="Cari alat" autocomplete="off">
                                @if ($jenis !== '')
                                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                                @endif
                            </div>
                            <button type="submit" class="home-btn home-btn-accent home-search-submit">
                                Cari alat
                            </button>
                            <button type="button" class="home-btn home-btn-glass home-scan-trigger" id="scanOpenBtn">
                                <x-icon name="qr-code" size="17" />
                                <span>Pindai</span>
                            </button>
                        </form>
                        <p class="home-search-hint">
                            <x-icon name="qr-code" size="13" />
                            Dukung pencarian lewat kode label/QR fisik barang.
                        </p>

                        <div class="home-hero-actions">
                            <a class="home-btn home-btn-accent home-btn-lg" href="#katalog">
                                Jelajahi Katalog Alat
                                <x-icon name="arrow-right" size="17" />
                            </a>
                            @auth
                                <a class="home-btn home-btn-glass home-btn-lg" href="{{ route('dashboard') }}">
                                    Cek Status Pinjaman Saya
                                </a>
                            @else
                                <a class="home-btn home-btn-glass home-btn-lg" href="{{ route('login') }}">
                                    Cek Status Pinjaman Saya
                                </a>
                            @endauth
                        </div>

                        <dl class="home-hero-stats">
                            <div>
                                <dt>Tersedia</dt>
                                <dd>{{ number_format($tersedia, 0, ',', '.') }} <span>unit</span></dd>
                            </div>
                            <div>
                                <dt>Sedang dipinjam</dt>
                                <dd>{{ number_format($dipinjam, 0, ',', '.') }} <span>unit</span></dd>
                            </div>
                            <div>
                                <dt>Perawatan</dt>
                                <dd>{{ number_format($perawatan, 0, ',', '.') }} <span>unit</span></dd>
                            </div>
                        </dl>
                        <p class="home-stats-note">
                            Diperbarui {{ now()->locale('id')->translatedFormat('d F Y, H.i') }} WIB
                            &middot; {{ $totalAlat }} jenis alat terdaftar
                        </p>
                    </div>

                    <div class="home-hero-visual" aria-hidden="true">
                        <div class="home-hero-glow"></div>
                        <div class="home-mosaic">
                            <div class="home-tile"><x-icon name="wifi" size="26" /><span>Jaringan</span></div>
                            <div class="home-tile"><x-icon name="cpu" size="26" /><span>Komputer</span></div>
                            <div class="home-tile"><x-icon name="layers" size="26" /><span>Inventaris</span></div>
                            <div class="home-tile"><x-icon name="qr-code" size="26" /><span>Label QR</span></div>
                            <div class="home-tile"><x-icon name="box" size="26" /><span>Stok</span></div>
                            <div class="home-tile"><x-icon name="wrench" size="26" /><span>Toolman</span></div>
                            <div class="home-tile"><x-icon name="zap" size="26" /><span>IoT</span></div>
                            <div class="home-tile"><x-icon name="shield" size="26" /><span>Aman</span></div>
                            <div class="home-tile"><x-icon name="clipboard" size="26" /><span>SOP</span></div>
                        </div>
                        <div class="home-float home-float-a">
                            <strong>{{ number_format($tersedia, 0, ',', '.') }}</strong>
                            <span>unit siap dipinjam</span>
                        </div>
                        <div class="home-float home-float-b">
                            <strong>{{ $totalAlat }}</strong>
                            <span>jenis alat terdaftar</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ================================================
                 2. KATALOG & FILTER KATEGORI
                 ================================================ -->
            <section class="home-section home-catalog" id="katalog">
                <div class="home-section-head">
                    <div>
                        <span class="home-eyebrow">Katalog inventaris</span>
                        <h2>Temukan alat yang kamu butuhkan</h2>
                        <p>
                            Cari berdasarkan nama, kode inventaris, kategori, atau lokasi penyimpanan.
                            Tambahkan beberapa alat sekaligus ke keranjang peminjaman.
                        </p>
                    </div>
                    <div class="home-catalog-count">
                        <strong>{{ $dibatasi ? $tampilAlats->count() : $totalHasil }}</strong>
                        <span>{{ $dibatasi ? 'contoh dari ' . $totalAlat : 'dari ' . $totalAlat }} alat</span>
                    </div>
                </div>

                <div class="home-catalog-toolbar">
                    <form class="home-catalog-search" method="GET" action="{{ route('home') }}#katalog" role="search">
                        <x-icon name="search" size="18" />
                        <input type="search" name="q" value="{{ $q }}"
                               placeholder="Cari alat atau kode inventaris..."
                               aria-label="Cari alat di katalog" autocomplete="off">
                        @if ($jenis !== '')
                            <input type="hidden" name="jenis" value="{{ $jenis }}">
                        @endif
                        <button type="submit">Filter</button>
                    </form>

                    <div class="home-chips" role="list" aria-label="Filter kategori alat">
                        <a class="home-chip {{ $jenis === '' ? 'is-active' : '' }}"
                           href="{{ route('home', array_filter(['q' => $q])) }}#katalog" role="listitem">
                            Semua <span>{{ $totalAlat }}</span>
                        </a>
                        @foreach ($kategori as $kat)
                            <a class="home-chip {{ $jenis === $kat['value'] ? 'is-active' : '' }}"
                               href="{{ route('home', array_filter(['q' => $q, 'jenis' => $kat['value']])) }}#katalog"
                               role="listitem">
                                {{ $kat['label'] }} <span>{{ $kat['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($alats->isEmpty())
                    <div class="home-empty">
                        <span class="home-empty-icon"><x-icon name="search" size="30" /></span>
                        <h3>Alat tidak ditemukan</h3>
                        <p>
                            Tidak ada alat yang cocok dengan pencarian
                            @if ($q !== '') <strong>&ldquo;{{ $q }}&rdquo;</strong> @endif.
                            Coba kata kunci lain atau lihat semua kategori.
                        </p>
                        <a class="home-btn home-btn-primary" href="{{ route('home') }}#katalog">
                            Tampilkan semua alat
                        </a>
                    </div>
                @else
                    @if ($dibatasi)
                        <div class="home-carousel-shell">
                    @endif

                    <div class="home-equipment-grid {{ $dibatasi ? 'is-carousel' : '' }}"
                         id="alatTrack"
                         @if ($dibatasi) role="region" aria-label="Contoh alat, geser atau gunakan tombol untuk melihat lainnya" @endif>
                        @foreach ($tampilAlats as $alat)
                            @php
                                $ready = (int) $alat->jumlah_stok;
                                $totalFisik = $alat->totalFisik();
                                $gambar = $alat->img ? asset($alat->img) : asset('svg/equipment-placeholder.svg');
                            @endphp

                            <article class="home-card" data-alat-card="{{ $alat->id }}">
                                <div class="home-card-media">
                                    <img src="{{ $gambar }}" alt="{{ $alat->nama }}" loading="lazy" decoding="async">
                                    <span class="home-stock-badge {{ $ready > 0 ? 'is-ready' : 'is-empty' }}">
                                        {{ $ready > 0 ? 'Ready' : 'Out of stock' }}
                                    </span>
                                </div>

                                <div class="home-card-body">
                                    <span class="home-card-category">{{ \App\Models\Alat::jenisLabel($alat->jenis) }}</span>
                                    <h3 title="{{ $alat->nama }}">{{ $alat->nama }}</h3>
                                    <p class="home-card-desc">{{ \Illuminate\Support\Str::limit($alat->description, 110) }}</p>

                                    <div class="home-card-meta">
                                        <span><x-icon name="tag" size="14" /> {{ $alat->code }}</span>
                                        <span><x-icon name="map-pin" size="14" /> {{ $alat->lokasiLabel() }}</span>
                                    </div>

                                    <div class="home-card-foot">
                                        <div class="home-card-stock">
                                            <strong>{{ $ready }}</strong>
                                            <span>tersedia dari {{ $totalFisik }} unit</span>
                                        </div>

                                        <button type="button" class="home-card-add"
                                                data-add-alat="{{ $alat->id }}"
                                                @disabled($ready <= 0)>
                                            <x-icon name="{{ $ready > 0 ? 'plus' : 'box' }}" size="16" />
                                            {{ $ready > 0 ? 'Tambah' : 'Stok habis' }}
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($dibatasi)
                        </div>
                    @endif

                    @if ($dibatasi)
                        <div class="home-carousel-controls" id="alatCarouselControls">
                            <button type="button" class="home-carousel-btn" id="alatPrev"
                                    aria-label="Alat sebelumnya" aria-controls="alatTrack">
                                <x-icon name="arrow-left" size="18" />
                            </button>

                            <div class="home-carousel-dots" id="alatDots" aria-label="Navigasi alat"></div>

                            <button type="button" class="home-carousel-btn" id="alatNext"
                                    aria-label="Alat berikutnya" aria-controls="alatTrack">
                                <x-icon name="arrow-right" size="18" />
                            </button>
                        </div>
                    @endif

                    @if ($dibatasi)
                        <div class="home-catalog-more">
                            <p>
                                Menampilkan {{ $tampilAlats->count() }} alat sebagai contoh.
                                Gunakan pencarian atau filter kategori untuk menemukan alat lain.
                            </p>
                            <a class="home-btn home-btn-primary" href="{{ route('home', ['semua' => 1]) }}#katalog">
                                Tampilkan semua {{ $totalHasil }} alat
                                <x-icon name="arrow-right" size="16" />
                            </a>
                        </div>
                    @elseif ($semua && $totalHasil > 6)
                        <div class="home-catalog-more">
                            <a class="home-btn home-btn-ghost" href="{{ route('home', array_filter(['q' => $q, 'jenis' => $jenis])) }}#katalog">
                                Ringkas tampilan
                            </a>
                        </div>
                    @endif
                @endif
            </section>

            <!-- ================================================
                 3. ALUR & PROSEDUR PEMINJAMAN
                 ================================================ -->
            <section class="home-section home-workflow" id="alur">
                <div class="home-section-head home-section-head-center">
                    <div>
                        <span class="home-eyebrow">Borrowing workflow</span>
                        <h2>Empat langkah, alat siap dipakai</h2>
                        <p>Alur pengajuan yang jelas dari katalog sampai serah terima fisik di ruang alat.</p>
                    </div>
                </div>

                <ol class="home-steps">
                    <li class="home-step">
                        <span class="home-step-num">01</span>
                        <span class="home-step-icon"><x-icon name="cart" size="22" /></span>
                        <h3>Pilih alat</h3>
                        <p>Cari barang di katalog, lalu masukkan beberapa alat sekaligus ke keranjang peminjaman.</p>
                    </li>
                    <li class="home-step">
                        <span class="home-step-num">02</span>
                        <span class="home-step-icon"><x-icon name="file-text" size="22" /></span>
                        <h3>Isi form pengajuan</h3>
                        <p>Tentukan tanggal pinjam, estimasi pengembalian, kegiatan praktikum, dan guru pembimbing.</p>
                    </li>
                    <li class="home-step">
                        <span class="home-step-num">03</span>
                        <span class="home-step-icon"><x-icon name="check-circle" size="22" /></span>
                        <h3>Verifikasi &amp; persetujuan</h3>
                        <p>Laboran, toolman, atau guru praktikum memeriksa ketersediaan dan menyetujui pengajuan.</p>
                    </li>
                    <li class="home-step">
                        <span class="home-step-num">04</span>
                        <span class="home-step-icon"><x-icon name="qr-code" size="22" /></span>
                        <h3>Pengambilan barang</h3>
                        <p>Tunjukkan kode QR atau ID transaksi di ruang penyimpanan alat untuk serah terima fisik.</p>
                    </li>
                </ol>
            </section>

            <!-- ================================================
                 4. REGULASI & OPERASIONAL LAB
                 ================================================ -->
            <section class="home-section home-regulation" id="regulasi">
                <div class="home-regulation-panel">
                    <div class="home-regulation-head">
                        <span class="home-eyebrow home-eyebrow-light">Regulasi &amp; operasional</span>
                        <h2>Aturan main di ruang alat</h2>
                        <p>Pahami jadwal layanan, durasi peminjaman, dan prosedur pelaporan sebelum mengambil alat.</p>
                    </div>

                    <div class="home-regulation-grid">
                        <article class="home-reg-card">
                            <span class="home-reg-icon"><x-icon name="clock" size="22" /></span>
                            <h3>Jadwal pelayanan toolman</h3>
                            <ul class="home-hours">
                                @foreach ($lab['jam_layanan'] as $jadwal)
                                    <li>
                                        <span>{{ $jadwal['hari'] }}</span>
                                        <strong>{{ $jadwal['jam'] }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </article>

                        <article class="home-reg-card">
                            <span class="home-reg-icon"><x-icon name="shield" size="22" /></span>
                            <h3>Aturan &amp; ketentuan peminjaman</h3>
                            <ul class="home-rules">
                                <li>Peminjaman selama jam pelajaran wajib dikembalikan sebelum jam sekolah berakhir.</li>
                                <li>Pinjam bawa pulang hanya dengan izin guru pembimbing dan laboran.</li>
                                <li>Keterlambatan pengembalian dicatat sebagai pelanggaran peminjaman.</li>
                                <li>Alat yang rusak atau hilang menjadi tanggung jawab peminjam sesuai ketentuan sekolah.</li>
                            </ul>
                        </article>

                        <article class="home-reg-card" id="regulasi-sop">
                            <span class="home-reg-icon"><x-icon name="alert-triangle" size="22" /></span>
                            <h3>SOP pelaporan kerusakan</h3>
                            <ol class="home-rules home-rules-numbered">
                                <li>Hentikan penggunaan alat jika muncul kendala atau kerusakan.</li>
                                <li>Laporkan melalui menu Chat Admin dengan menyertakan nama alat dan kode inventaris.</li>
                                <li>Serahkan alat ke toolman untuk diperiksa; jangan memperbaiki sendiri.</li>
                                <li>Toolman mencatat kondisi alat dan memperbarui status perawatan di sistem.</li>
                            </ol>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ================================================
                 5. WIDGET AKTIVITAS SAYA
                 ================================================ -->
            <section class="home-section home-activity" id="aktivitas">
                <div class="home-section-head">
                    <div>
                        <span class="home-eyebrow">Aktivitas saya</span>
                        <h2>Status pinjaman dalam satu pandangan</h2>
                        <p>Pantau alat yang sedang dipinjam, tenggat pengembalian, dan aksi cepat.</p>
                    </div>
                </div>

                @auth
                    @if (auth()->user()->isAdmin())
                        <div class="home-activity-panel">
                            <div class="home-activity-hero">
                                <span class="home-activity-avatar"><x-icon name="layers" size="24" /></span>
                                <div>
                                    <h3>Panel cepat admin</h3>
                                    <p>Halo, {{ auth()->user()->name }}. Ada {{ $pendingApproval }} pengajuan menunggu persetujuan.</p>
                                </div>
                                <a class="home-btn home-btn-primary" href="{{ route('approval.index') }}">
                                    Buka approval
                                </a>
                            </div>
                            <div class="home-activity-links">
                                <a href="{{ route('admin.alat.index') }}"><x-icon name="box" size="16" /> Kelola data alat</a>
                                <a href="{{ route('admin.qr.index') }}"><x-icon name="qr-code" size="16" /> QR transaksi</a>
                                <a href="{{ route('admin.analisis') }}"><x-icon name="trending-up" size="16" /> Analisis peminjaman</a>
                                <a href="{{ route('admin.bukti.index') }}"><x-icon name="camera" size="16" /> Bukti transaksi</a>
                            </div>
                        </div>
                    @else
                        @if ($activeLoans->isEmpty())
                            <div class="home-activity-empty">
                                <span class="home-activity-avatar"><x-icon name="box" size="24" /></span>
                                <div>
                                    <h3>Belum ada pinjaman aktif</h3>
                                    <p>Kamu belum memiliki pengajuan atau peminjaman yang berjalan.</p>
                                </div>
                                <a class="home-btn home-btn-primary" href="#katalog">Mulai pilih alat</a>
                            </div>
                        @else
                            <div class="home-loan-list">
                                @foreach ($activeLoans as $loan)
                                    @php
                                        $badgeClass = match ($loan->status) {
                                            'pending' => 'is-waiting',
                                            'disetuji' => 'is-approved',
                                            'dipinjam' => 'is-active',
                                            default => 'is-muted',
                                        };
                                        $tenggat = $loan->dikembalikan_saat
                                            ? \Carbon\Carbon::parse($loan->dikembalikan_saat)->locale('id')->translatedFormat('d M Y, H.i')
                                            : 'Belum ditentukan';
                                    @endphp

                                    <article class="home-loan">
                                        <div class="home-loan-main">
                                            <span class="home-loan-status {{ $badgeClass }}">{{ $loan->status_label }}</span>
                                            <h3>{{ $loan->alat->nama ?? 'Alat dihapus' }}</h3>
                                            <p>
                                                <span><x-icon name="tag" size="13" /> {{ $loan->alat->code ?? '-' }}</span>
                                                <span><x-icon name="calendar" size="13" /> Tenggat {{ $tenggat }}</span>
                                                <span><x-icon name="box" size="13" /> {{ $loan->jumlah_minta }} unit</span>
                                            </p>
                                            @if ($loan->hari_terlambat > 0)
                                                <p class="home-loan-late">
                                                    <x-icon name="alert-triangle" size="13" />
                                                    Terlambat {{ $loan->hari_terlambat }} hari
                                                </p>
                                            @endif
                                        </div>
                                        <div class="home-loan-actions">
                                            <a class="home-btn home-btn-ghost" href="{{ route('dashboard') }}">Lihat detail</a>
                                            <a class="home-btn home-btn-ghost" href="{{ route('chat.index', ['topik' => 'perpanjangan']) }}">
                                                Perpanjang
                                            </a>
                                            <a class="home-btn home-btn-ghost" href="{{ route('chat.index', ['topik' => 'kerusakan']) }}">
                                                Lapor kendala
                                            </a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @else
                    <div class="home-activity-guest">
                        <div class="home-activity-guest-copy">
                            <span class="home-activity-avatar"><x-icon name="user" size="24" /></span>
                            <div>
                                <h3>Masuk untuk melihat aktivitasmu</h3>
                                <p>
                                    Status pinjaman, tenggat pengembalian, dan aksi perpanjangan
                                    tersedia setelah kamu masuk ke akun SIJALAT.
                                </p>
                            </div>
                        </div>
                        <div class="home-activity-guest-actions">
                            <a class="home-btn home-btn-primary" href="{{ route('login') }}">Masuk ke akun</a>
                            <a class="home-btn home-btn-ghost" href="{{ route('register') }}">Daftar siswa</a>
                        </div>
                    </div>
                @endauth
            </section>

            <!-- ================================================
                 6. FOOTER & KONTAK LABORATORIUM
                 ================================================ -->
            <footer class="home-footer" id="kontak">
                <div class="home-footer-inner">
                    <div class="home-footer-main">
                        <div class="home-footer-brand">
                            <a class="home-brand home-brand-light" href="{{ route('home') }}">
                                <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="" width="38" height="38">
                                <span>SIJALAT</span>
                            </a>
                            <p>
                                Sistem Informasi Peminjaman Alat &amp; Inventaris Jurusan
                                {{ $lab['jurusan'] }}. Dibuat untuk memudahkan pelayanan
                                praktikum dan kegiatan pembelajaran siswa.
                            </p>
                        </div>

                        <div class="home-footer-col">
                            <h3>Kontak laboratorium</h3>
                            @foreach ($lab['kontak'] as $kontak)
                                <div class="home-contact">
                                    <span class="home-contact-role">{{ $kontak['peran'] }}</span>
                                    <span class="home-contact-name">{{ $kontak['nama'] }}</span>
                                    <a href="mailto:{{ $kontak['email'] }}">
                                        <x-icon name="mail" size="14" /> {{ $kontak['email'] }}
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="home-footer-col">
                            <h3>Tautan penting</h3>
                            <ul class="home-footer-links">
                                <li>
                                    <a href="{{ auth()->check() ? route('guide') : route('login') }}">
                                        <x-icon name="book-open" size="14" /> Panduan penggunaan sistem
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ asset('docs/sop-keselamatan-lab.html') }}" target="_blank" rel="noopener">
                                        <x-icon name="shield" size="14" /> SOP keselamatan kerja lab
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ asset('docs/form-laporan-sarpras.html') }}" download>
                                        <x-icon name="file-text" size="14" /> Unduh form laporan sarpras
                                    </a>
                                </li>
                                <li>
                                    <a href="#katalog">
                                        <x-icon name="box" size="14" /> Jelajahi katalog alat
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="home-footer-bottom">
                        <span>&copy; {{ date('Y') }} SIJALAT &middot; {{ $lab['sekolah'] }}</span>
                        <span>{{ $lab['jurusan'] }} &middot; Peminjaman alat jadi lebih mudah</span>
                    </div>
                </div>
            </footer>
        </main>

        <!-- ================================================
             KERANJANG MULTI-ITEM
             ================================================ -->
        <button type="button" class="home-cart-fab" id="cartFab" hidden aria-label="Buka keranjang peminjaman">
            <x-icon name="cart" size="19" />
            <span>Keranjang</span>
            <b id="cartFabCount">0</b>
        </button>

        <div class="home-cart-overlay" id="cartOverlay" aria-hidden="true">
            <div class="home-cart-backdrop" data-cart-close></div>

            <aside class="home-cart-panel" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
                <header class="home-cart-head">
                    <div>
                        <h2 id="cartTitle">Keranjang peminjaman</h2>
                        <p>Kumpulkan beberapa alat dalam satu pengajuan praktikum.</p>
                    </div>
                    <button type="button" class="home-icon-btn" data-cart-close aria-label="Tutup keranjang">
                        <x-icon name="x" size="18" />
                    </button>
                </header>

                <div class="home-cart-body" id="cartItems"></div>

                <div class="home-cart-empty" id="cartEmpty">
                    <span><x-icon name="cart" size="28" /></span>
                    <h3>Keranjang masih kosong</h3>
                    <p>Tambahkan alat dari katalog dengan tombol <strong>Tambah</strong>.</p>
                </div>

                <footer class="home-cart-foot">
                    <div class="home-cart-total">
                        <span>Total unit</span>
                        <strong id="cartTotal">0</strong>
                    </div>
                    <p class="home-cart-note" id="cartNote"></p>
                    <a class="home-btn home-btn-accent home-btn-block" id="cartSubmit" href="{{ route('login') }}">
                        Masuk untuk mengajukan
                    </a>
                </footer>
            </aside>
        </div>

        <!-- ================================================
             MODAL SCANNER QR / BARCODE
             ================================================ -->
        <div class="home-scan-overlay" id="scanModal" aria-hidden="true">
            <div class="home-scan-backdrop" data-scan-close></div>

            <div class="home-scan-dialog" role="dialog" aria-modal="true" aria-labelledby="scanTitle">
                <header class="home-scan-head">
                    <div>
                        <h2 id="scanTitle">Pindai kode alat</h2>
                        <p>Arahkan kamera ke label QR atau masukkan kode inventaris manual.</p>
                    </div>
                    <button type="button" class="home-icon-btn" data-scan-close aria-label="Tutup pemindai">
                        <x-icon name="x" size="18" />
                    </button>
                </header>

                <div class="home-scan-view">
                    <video id="scanVideo" playsinline muted></video>
                    <div class="home-scan-placeholder" id="scanPlaceholder">
                        <x-icon name="camera" size="30" />
                        <p id="scanStatus">Kamera belum aktif. Tekan tombol di bawah untuk mulai memindai.</p>
                    </div>
                </div>

                <div class="home-scan-actions">
                    <button type="button" class="home-btn home-btn-primary" id="scanStart">Aktifkan kamera</button>
                    <button type="button" class="home-btn home-btn-ghost" id="scanStop" hidden>Hentikan kamera</button>
                </div>

                <form class="home-scan-manual" id="scanManual">
                    <label for="scanCode">Atau masukkan kode inventaris</label>
                    <div>
                        <input type="text" id="scanCode" name="code" placeholder="Contoh: 02.09.03.05.11" autocomplete="off">
                        <button type="submit" class="home-btn home-btn-primary">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="home-toast" id="homeToast" role="status" aria-live="polite"></div>

        <script>
            window.SIJALAT_HOME = {
                alats: @json($alatJson),
                authenticated: @json(auth()->check()),
                flash: @json(session('success')),
                homeUrl: @json(route('home', absolute: false)),
                borrowUrl: @json(route('pinjam.create', absolute: false)),
                loginUrl: @json(route('login', absolute: false)),
            };
        </script>
        <script src="{{ asset('js/home.js') }}"></script>
    </body>
</html>
