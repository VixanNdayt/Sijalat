<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/pinjam.css') }}">

        @stack('styles')
    </head>
    <body class="page-pinjam">
        <div class="overlay" id="overlay"></div>

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="">
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <header>
                <h2>Peminjaman Alat</h2>
                <p>Periksa setiap ketersediaan alat dan tentukan pilihanmu sesuai kebutuhanmu</p>
            </header>
            
            <div class="main-con">
                
                <div class="left-information">

                    <div class="search-div">
                        <p>Cari alat</p>
                        <div>
                            <input type="text" id="searchInput" placeholder="Cari alat...">

                            <div class="filter-select" id="filterSelect">
                                <button
                                    type="button"
                                    class="filter-select-trigger"
                                    id="filterKategoriTrigger"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    aria-controls="filterKategoriMenu"
                                >
                                    <span id="filterKategoriLabel">Semua Jenis</span>
                                    <x-icon name="chevron-down" size="16" />
                                </button>

                                <ul
                                    class="filter-select-menu"
                                    id="filterKategoriMenu"
                                    role="listbox"
                                    aria-label="Filter jenis alat"
                                    hidden
                                >
                                    <li
                                        class="filter-select-option is-selected"
                                        role="option"
                                        data-value=""
                                        aria-selected="true"
                                        tabindex="-1"
                                    >
                                        Semua Jenis
                                    </li>
                                    @foreach ($jenisAlats as $jenis)
                                        <li
                                            class="filter-select-option"
                                            role="option"
                                            data-value="{{ $jenis['value'] }}"
                                            aria-selected="false"
                                            tabindex="-1"
                                        >
                                            {{ $jenis['label'] }}
                                        </li>
                                    @endforeach
                                </ul>

                                <select id="filterKategori" class="filter-select-native" aria-hidden="true" tabindex="-1">
                                    <option value="">Semua Jenis</option>
                                    @foreach ($jenisAlats as $jenis)
                                        <option value="{{ $jenis['value'] }}">{{ $jenis['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="button" id="btnSearch">Search</button>
                        </div>
                    </div>

                    <!-- Item selection -->
                    <div class="item-sec">
                        @foreach ($alats as $alat)
                            @php($ready = $alat->stok_tersedia ?? $alat->jumlah_stok)
                            <article class="item-box" data-nama="{{ strtolower($alat->nama) }}" data-kategori="{{ strtolower($alat->jenis) }}">
                                <div class="img-item">
                                    <img src="{{ asset($alat->img) }}" alt="{{ $alat->nama }}" loading="lazy">
                                    <span class="item-status {{ $ready > 0 ? 'is-ready' : 'is-empty' }}">
                                        {{ $ready > 0 ? 'Tersedia' : 'Habis' }}
                                    </span>
                                </div>

                                <div class="item-box-des">
                                    <h4 class="item-name">{{ $alat->nama }}</h4>
                                    <p class="item-desc">{{ $alat->description }}</p>

                                    <div class="item-stats">
                                        <div class="item-stat">
                                            <span class="item-stat-label">Stok</span>
                                            <span class="item-stat-value">{{ $alat->jumlah_stok }}</span>
                                        </div>
                                        <div class="item-stat">
                                            <span class="item-stat-label">Tersedia</span>
                                            <span class="item-stat-value">{{ $ready }}</span>
                                        </div>
                                    </div>

                                    <!-- Tombol Tambahkan memanggil fungsi JavaScript -->
                                    <button
                                        type="button"
                                        class="btn-primary item-add"
                                        onclick="pilihBarang({{ $alat->id }}, @js($alat->nama))"
                                    >
                                        <x-icon name="plus" size="15" /> Tambahkan
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                </div>

                <div class="right-information">
                    <!-- Ringkasan keranjang + tombol pembuka form (melayang di HP) -->
                    <div class="keranjang-panel {{ $errors->any() || count($oldCart) ? 'is-open' : '' }}" id="keranjang-panel">
                        <div class="keranjang-panel-head">
                            <h3><x-icon name="cart" size="17" /> Keranjang</h3>
                            <div class="keranjang-head-right">
                                <span class="keranjang-count" id="keranjang-count">0</span>
                                <button type="button" class="keranjang-toggle" id="btnToggleKeranjang"
                                        aria-expanded="{{ $errors->any() || count($oldCart) ? 'true' : 'false' }}"
                                        aria-controls="keranjang-detail" aria-label="Tampilkan rincian keranjang">
                                    <x-icon name="chevron-down" size="18" />
                                </button>
                            </div>
                        </div>

                        <div class="keranjang-detail" id="keranjang-detail">
                            <a class="a-a" href="{{ route('pinjam.bukti') }}">
                                <x-icon name="qr-code" size="15" />
                                <span>Scan QR pengambilan / pengembalian</span>
                            </a>

                            <div class="keranjang">
                                <p id="keranjang-kosong" class="keranjang-kosong">Belum ada alat yang dipilih.</p>
                                <div id="keranjang-summary"></div>
                            </div>

                            @if ($errors->any())
                                <div class="keranjang-error">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <p class="keranjang-hint">Pilih alat di katalog, lalu isi form peminjaman.</p>
                        </div>

                        <button type="button" class="btn-primary" id="btnBukaForm" onclick="bukaFormPeminjaman()" disabled>
                            <x-icon name="file-text" size="16" /> Isi Form Peminjaman
                        </button>
                    </div>
                </div>
                
            </div>
            @include('partials.footer')
        </main>

        <!-- ============================================ -->
        <!-- MODAL FORM PEMINJAMAN                        -->
        <!-- ============================================ -->
        <div class="modal" id="modal-pinjam" role="dialog" aria-modal="true" aria-labelledby="judulFormPinjam">
            <div class="modal-backdrop" data-modal-close></div>

            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 id="judulFormPinjam">Form Peminjaman</h3>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
                        <x-icon name="x" size="18" />
                    </button>
                </div>

                <div class="modal-body">
                    <form action="{{ route('pinjam.store') }}" method="POST" class="form-main" id="formPeminjaman">
                        @csrf

                        <div class="input">
                            <label>Nama</label>
                            <input type="text" value="{{ Auth::user()->name }}" readonly>
                        </div>

                        <div class="input">
                            <label>NIS</label>
                            <input type="text" value="{{ Auth::user()->nis }}" readonly>
                        </div>

                        <div class="input">
                            <label>Keranjang Alat</label>
                            <!-- Input asli (alat_id[] & jumlah_minta[]) tinggal di sini -->
                            <div class="keranjang keranjang-modal">
                                <p id="keranjang-modal-kosong" class="keranjang-kosong">Belum ada alat yang dipilih.</p>
                                <div id="keranjang-container"></div>
                            </div>
                        </div>

                        <div class="input">
                            <label>Waktu Ambil &amp; Kembalikan</label>
                            <div class="two-form">
                                <input type="datetime-local" name="dipinjam_saat" value="{{ old('dipinjam_saat') }}" required> -
                                <input type="datetime-local" name="dikembalikan_saat" value="{{ old('dikembalikan_saat') }}" required>
                            </div>
                            <p class="form-hint">
                                Hanya jam sekolah: Senin 08.00&ndash;16.30, Selasa&ndash;Kamis 07.00&ndash;16.35, Jumat 08.00&ndash;14.45.
                            </p>
                        </div>

                        <div class="input">
                            <label>Ruangan</label>
                            <input type="text" name="ruangan" value="{{ old('ruangan') }}" required>
                        </div>

                        <div class="input">
                            <label>Keterangan</label>
                            <textarea name="deskripsi_minta" rows="2" required>{{ old('deskripsi_minta') }}</textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Data untuk JavaScript -->
        <script>
            window.alatMap = @json($alats->pluck('nama', 'id'));
            window.stokMap = @json($alats->pluck('jumlah_stok', 'id'));
            window.oldCart = @json($oldCart);
        </script>

            <!-- Sidebar -->
        @include('partials.sidebar-siswa')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/pinjam.js') }}"></script>
    </body>
</html>
