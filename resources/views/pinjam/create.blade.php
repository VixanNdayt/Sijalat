<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/pinjam.css') }}">

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
                            <select id="filterKatgeori">
                                <option>All</option>
                                <option value="laptop">Laptop</option>
                            </select>
                            <button type="submit">Search</button>
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
                                    <button type="button" onclick="pilihBarang({{ $alat->id }}, '{{ $alat->nama }}')">
                                        Tambahkan
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
                <div class="right-information">
                    <a class="a-a" href="">
                        <p>Masuk ke page pengambilan/pengembalian</p>
                    </a>
                    <div class="form-peminjaman">
                        <h3>Form Peminjaman</h3>

                        @if ($errors->any())
                            <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('pinjam.store') }}" method="POST" class="form-main">
                            @csrf

                            <input type="hidden" name="alat_id" id="alat_id_input">

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
                                <!-- Kotak pembungkus keranjang -->
                                <div class="keranjang">
                                    <p id="keranjang-kosong" class="keranjang-kosong" style="color: gray; font-size: 14px; font-style: italic;">Belum ada alat yang dipilih.</p>
                                    <!-- Daftar barang akan otomatis disisipkan JavaScript di dalam div bawah ini -->
                                    <div id="keranjang-container" style="display: flex; flex-direction: column; gap: 10px;"></div>
                                </div>
                            </div>

                            <div class="input">
                                <label>Waktu Ambil & kembalikan</label>
                                <div class="two-form">
                                    <input type="datetime-local" name="dipinjam_saat" required> -
                                    <input type="datetime-local" name="dikembalikan_saat" required>
                                </div>
                            </div>

                            <div class="input">
                                <label>Keterangan</label>
                                <textarea name="deskripsi_minta" rows="2" required></textarea>
                            </div>

                            <button type="submit" class="submit-btn">
                                Submit
                            </button>
                        </form>
                    </div>
                </div>
                
            </div>
        </main>

            <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('pinjam.create') }}">Peminjaman</a>
                <a>Riwayat</a>
                <a>Report</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/pinjam.js') }}"></script>
    </body>
</html>