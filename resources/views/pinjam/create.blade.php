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
                            <select id="filterKategori">
                                <option value="">All</option>
                                <option value="laptop">Laptop</option>
                            </select>
                            <button type="button">Search</button>
                        </div>
                    </div>

                    <!-- Item selection -->
                    <div class="item-sec">
                        @foreach ($alats as $alat)
                            <div class="item-box" data-nama="{{ strtolower($alat->nama) }}" data-kategori="{{ strtolower($alat->jenis) }}">
                                <div class="img-item">
                                    <img src="{{ asset($alat->img) }}" alt="foto">
                                </div>

                                <div class="item-box-des">
                                    <h4>{{ $alat->nama }}</h4>
                                    <p>Deskripsi :<br>{{ $alat->description }}</p>
                                    <p>Qty : {{ $alat->jumlah_stok }} | Ready : {{ $alat->stok_tersedia ?? $alat->jumlah_stok }}</p>

                                    <!-- Tombol Tambahkan memanggil fungsi JavaScript -->
                                    <button type="button" onclick="pilihBarang({{ $alat->id }}, @js($alat->nama))">
                                        Tambahkan
                                    </button>
                                </div>
                            </div>
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
                            <label>Waktu Ambil &amp; kembalikan</label>
                            <div class="two-form">
                                <input type="datetime-local" name="dipinjam_saat" value="{{ old('dipinjam_saat') }}" required> -
                                <input type="datetime-local" name="dikembalikan_saat" value="{{ old('dikembalikan_saat') }}" required>
                            </div>
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
            window.oldCart = @json($oldCart);
        </script>

            <!-- Sidebar -->
        @include('partials.sidebar-siswa')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/pinjam.js') }}"></script>
    </body>
</html>
