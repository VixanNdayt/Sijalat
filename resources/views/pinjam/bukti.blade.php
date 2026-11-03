<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'SIJALAT - Bukti Peminjaman')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/bukti.css') }}">

        @stack('styles')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <!-- Main Content -->
        <main class="main-content">

            <!-- navbar -->
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="User">
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <div class="main-container">

                <!-- Header -->
                <div class="page-top-bar">
                    <div>
                        <h2>Pengambilan &amp; Pengembalian</h2>
                        <p>Minta guru menampilkan QR transaksi Anda, lalu scan QR tersebut untuk verifikasi</p>
                    </div>
                </div>

                <!-- Alert Notifikasi Error / Sukses -->
                @if (session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Tombol Tab -->
                <div class="tab-container">
                    <button type="button" id="btn-ambil" class="tab-btn active" onclick="switchTab('ambil')">1. PENGAMBILAN ALAT</button>
                    <button type="button" id="btn-kembali" class="tab-btn" onclick="switchTab('kembali')">2. PENGEMBALIAN ALAT</button>
                </div>
            
                <!-- ============================================ -->
                <!-- 1. BUNGKUSAN FORM PENGAMBILAN -->
                <!-- ============================================ -->
                <div id="form-ambil" class="form-wrapper">
                    <div class="form-header">
                        <h3>Verifikasi Pengambilan di Lab</h3>
                        <p>Scan <strong>QR Pengambilan</strong> yang ditampilkan guru untuk transaksi yang akan Anda ambil</p>
                    </div>

                    <!-- Area Verifikasi QR transaksi (Pengambilan) -->
                    <div class="qr-box">
                        <label class="qr-label"><x-icon name="qr-code" size="16" /> Scan QR Pengambilan dari Guru</label>
                        <div class="qr-actions">
                            <button type="button" class="btn-scan" id="btn-scan-ambil" onclick="toggleScanner('ambil')">
                                <x-icon name="camera" size="16" /> <span class="btn-label">Buka Kamera Scan QR</span>
                            </button>
                            <span class="qr-or">atau</span>
                            <div class="qr-input-group">
                                <input type="text" id="input-kode-ambil" placeholder="Tempel/ketik kode QR dari guru">
                                <button type="button" class="btn-cek" onclick="cariKode('ambil')">Verifikasi</button>
                            </div>
                        </div>

                        <!-- Wadah Kamera Scanner Pengambilan -->
                        <div id="reader-ambil" class="qr-reader-container" style="display: none;"></div>

                        <!-- Feedback Status Verifikasi QR -->
                        <div id="qr-feedback-ambil" class="qr-feedback" style="display: none;"></div>
                    </div>
                
                    <form action="{{ route('transaksi.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="aksi" value="AMBIL">
                        <input type="hidden" name="pinjam_id" id="pinjam_id_ambil" value="{{ old('pinjam_id') }}">
                        <input type="hidden" name="qr_token" id="qr_token_ambil" value="{{ old('qr_token') }}">

                        <div class="input-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama" id="nama_ambil" value="{{ old('nama', auth()->user()->name) }}" placeholder="Masukkan nama" required readonly style="background-color: #f9fafb;">
                        </div>
                    
                        <div class="row">
                            <div class="input-group col">
                                <label>NIS</label>
                                <input type="text" name="nis" id="nis_ambil" value="{{ old('nis', auth()->user()->nis) }}" placeholder="Masukkan NIS" required readonly style="background-color: #f9fafb;">
                            </div>
                            <div class="input-group col">
                                <label>Kelas</label>
                                @php $kelasSiswa = auth()->user()->kelas ?? ''; @endphp
                                <input type="text" name="kelas" id="kelas_ambil" value="{{ old('kelas', $kelasSiswa) }}" placeholder="Kelas belum diisi, lengkapi di Profil" @if ($kelasSiswa) readonly style="background-color: #f9fafb;" @endif>
                                @if (! $kelasSiswa && ! old('kelas'))
                                    <small class="field-hint">Kelas belum ada di profil. <a href="{{ route('profile.edit') }}">Lengkapi profil</a> agar terisi otomatis.</small>
                                @endif
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Nama Barang / Alat</label>
                            <input type="text" name="barang" id="barang_ambil" placeholder="Terisi otomatis setelah scan QR dari guru" value="{{ old('barang') }}" required readonly style="background-color: #f9fafb;">
                        </div>
                    
                        <div class="input-group">
                            <label>Waktu dan Tempat Penggunaan</label>
                            <div class="row">
                                <input type="time" name="waktu_mulai" id="waktu_mulai_ambil" class="col" value="{{ old('waktu_mulai', date('H:i')) }}">
                                <input type="time" name="waktu_selesai" id="waktu_selesai_ambil" class="col" value="{{ old('waktu_selesai') }}">
                            </div>
                            <input type="text" name="tempat" id="tempat_ambil" placeholder="Lokasi penggunaan (misal: Ruang Lab Jaringan)" value="{{ old('tempat') }}" style="margin-top: 10px;">
                            <small class="auto-fill-hint" id="hint-jadwal-ambil" style="display: none;"></small>
                        </div>
                    
                        <div class="input-group">
                            <label>Catatan Awal Pengambilan</label>
                            <textarea name="catatan" id="catatan_ambil" rows="3" placeholder="Kondisi awal alat saat diambil di lab...">{{ old('catatan') }}</textarea>
                        </div>
                    
                        <!-- Upload Foto Bukti Pengambilan -->
                        <div class="input-group">
                            <label for="foto-ambil" class="upload-box" id="label-foto-ambil">
                                <x-icon name="upload" size="22" /><br><span class="upload-text">Klik di sini untuk upload foto barang saat diambil di meja lab</span>
                            </label>
                            <input type="file" id="foto-ambil" name="bukti_foto" accept="image/*" required onchange="tampilkanNamaFile(this, 'label-foto-ambil')">
                        </div>
                    
                        <button type="submit" class="btn-submit" id="btn-submit-ambil">
                            Submit Bukti Pengambilan
                        </button>
                    </form>
                </div>
            
                <!-- ============================================ -->
                <!-- 2. BUNGKUSAN FORM PENGEMBALIAN -->
                <!-- ============================================ -->
                <div id="form-kembali" class="form-wrapper" style="display: none;">
                    <div class="form-header">
                        <h3>Verifikasi Pengembalian di Lab</h3>
                        <p>Scan <strong>QR Pengembalian</strong> yang ditampilkan guru untuk transaksi yang akan Anda kembalikan</p>
                    </div>

                    <!-- Area Verifikasi QR transaksi (Pengembalian) -->
                    <div class="qr-box">
                        <label class="qr-label"><x-icon name="qr-code" size="16" /> Scan QR Pengembalian dari Guru</label>
                        <div class="qr-actions">
                            <button type="button" class="btn-scan" id="btn-scan-kembali" onclick="toggleScanner('kembali')">
                                <x-icon name="camera" size="16" /> <span class="btn-label">Buka Kamera Scan QR</span>
                            </button>
                            <span class="qr-or">atau</span>
                            <div class="qr-input-group">
                                <input type="text" id="input-kode-kembali" placeholder="Tempel/ketik kode QR dari guru">
                                <button type="button" class="btn-cek" onclick="cariKode('kembali')">Verifikasi</button>
                            </div>
                        </div>

                        <!-- Wadah Kamera Scanner Pengembalian -->
                        <div id="reader-kembali" class="qr-reader-container" style="display: none;"></div>

                        <!-- Feedback Status Verifikasi QR -->
                        <div id="qr-feedback-kembali" class="qr-feedback" style="display: none;"></div>
                    </div>
                
                    <form action="{{ route('transaksi.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="aksi" value="KEMBALI">
                        <input type="hidden" name="pinjam_id" id="pinjam_id_kembali" value="{{ old('pinjam_id') }}">
                        <input type="hidden" name="qr_token" id="qr_token_kembali" value="{{ old('qr_token') }}">

                        <div class="input-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama" id="nama_kembali" value="{{ old('nama', auth()->user()->name) }}" placeholder="Masukkan nama" required readonly style="background-color: #f9fafb;">
                        </div>
                    
                        <div class="row">
                            <div class="input-group col">
                                <label>NIS</label>
                                <input type="text" name="nis" id="nis_kembali" value="{{ old('nis', auth()->user()->nis) }}" placeholder="Masukkan NIS" required readonly style="background-color: #f9fafb;">
                            </div>
                            <div class="input-group col">
                                <label>Kelas</label>
                                @php $kelasSiswa = $kelasSiswa ?? (auth()->user()->kelas ?? ''); @endphp
                                <input type="text" name="kelas" id="kelas_kembali" value="{{ old('kelas', $kelasSiswa) }}" placeholder="Kelas belum diisi, lengkapi di Profil" @if ($kelasSiswa) readonly style="background-color: #f9fafb;" @endif>
                                @if (! $kelasSiswa && ! old('kelas'))
                                    <small class="field-hint">Kelas belum ada di profil. <a href="{{ route('profile.edit') }}">Lengkapi profil</a> agar terisi otomatis.</small>
                                @endif
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Nama Barang / Alat yang Dikembalikan</label>
                            <input type="text" name="barang" id="barang_kembali" placeholder="Terisi otomatis setelah scan QR dari guru" value="{{ old('barang') }}" required readonly style="background-color: #f9fafb;">
                        </div>
                    
                        <div class="input-group">
                            <label>Waktu Pengembalian</label>
                            <input type="time" name="waktu_kembali" value="{{ old('waktu_kembali', date('H:i')) }}" style="width: 50%;">
                        </div>
                    
                        <div class="input-group">
                            <label>Catatan Akhir Pengembalian</label>
                            <textarea name="catatan" id="catatan_kembali" rows="3" placeholder="Kondisi alat saat diserahkan kembali di meja lab...">{{ old('catatan') }}</textarea>
                        </div>
                    
                        <!-- Upload Foto Bukti Pengembalian -->
                        <div class="input-group">
                            <label for="foto-kembali" class="upload-box" id="label-foto-kembali">
                                <x-icon name="upload" size="22" /><br><span class="upload-text">Klik di sini untuk upload foto barang saat dikembalikan di meja lab</span>
                            </label>
                            <input type="file" id="foto-kembali" name="bukti_foto" accept="image/*" required onchange="tampilkanNamaFile(this, 'label-foto-kembali')">
                        </div>
                    
                        <button type="submit" class="btn-submit" id="btn-submit-kembali">
                            Submit Bukti Pengembalian
                        </button>
                    </form>
                </div>
            </div>

            @include('partials.footer')

        </main>

        <!-- Sidebar -->
        @include('partials.sidebar-siswa')

        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/bukti.js') }}"></script>
    </body>
</html>
