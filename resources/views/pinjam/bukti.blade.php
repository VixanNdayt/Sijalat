<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                
                <h2>SIJALAT</h2>

                <button class="toggle-btn" id="toggleBtn">☰</button>
            </nav>

            <div class="main-container">

                <!-- Header & Tombol Cetak QR Meja -->
                <div class="page-top-bar">
                    <div>
                        <h2>Pengambilan & Pengembalian</h2>
                        <p>Verifikasi kehadiran di lab dengan men-scan QR Code yang tertempel di meja lab</p>
                    </div>
                    <button type="button" class="btn-modal-qr" onclick="bukaModalQR()">
                        🖨️ Cetak / Lihat QR Meja Lab
                    </button>
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
                        <p>Scan QR Code yang tertempel di <strong>Meja Pengambilan Lab</strong> untuk memverifikasi lokasi</p>
                    </div>

                    <!-- Area Verifikasi QR Code Meja Lab (Pengambilan) -->
                    <div class="qr-box">
                        <label class="qr-label">📍 Scan QR Code Meja Pengambilan Lab</label>
                        <div class="qr-actions">
                            <button type="button" class="btn-scan" id="btn-scan-ambil" onclick="toggleScanner('ambil')">
                                📷 Buka Kamera Scan QR Meja
                            </button>
                            <span class="qr-or">atau</span>
                            <div class="qr-input-group">
                                <input type="text" id="input-kode-ambil" placeholder="Ketik kode meja (SIJALAT-AMBIL-LAB)">
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
                        <input type="hidden" name="kode_meja" id="kode_meja_ambil" value="">
                        <input type="hidden" name="kode_pinjam" id="kode_pinjam_ambil" value="{{ old('kode_pinjam') }}">

                        <!-- Pilihan Peminjaman Aktif Siswa -->
                        @if(isset($pinjamanAmbil) && $pinjamanAmbil->count() > 0)
                            <div class="input-group">
                                <label>Pilih Pengajuan Peminjaman:</label>
                                <select id="select_pinjam_ambil" name="pinjam_id" onchange="pilihPeminjaman('ambil', this)" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Pilih dari riwayat pengajuan aktif --</option>
                                    @foreach($pinjamanAmbil as $p)
                                        <option value="{{ $p->id }}" 
                                                data-barang="{{ $p->alat->nama ?? '' }}" 
                                                data-kode="{{ $p->kode_pinjam }}"
                                                {{ old('pinjam_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->alat->nama ?? 'Alat' }} (Qty: {{ $p->jumlah_minta }}) - Status: {{ ucfirst($p->status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    
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
                                <input type="text" name="kelas" id="kelas_ambil" value="{{ old('kelas') }}" placeholder="Contoh: XI SIJA A">
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Nama Barang / Alat</label>
                            <input type="text" name="barang" id="barang_ambil" placeholder="Ketik nama alat atau pilih di atas" value="{{ old('barang', $pinjamanAmbil->first()->alat->nama ?? '') }}" required>
                        </div>
                    
                        <div class="input-group">
                            <label>Waktu dan Tempat Penggunaan</label>
                            <div class="row">
                                <input type="time" name="waktu_mulai" class="col" value="{{ old('waktu_mulai', date('H:i')) }}">
                                <input type="time" name="waktu_selesai" class="col" value="{{ old('waktu_selesai') }}">
                            </div>
                            <input type="text" name="tempat" placeholder="Lokasi penggunaan (misal: Ruang Lab Jaringan)" value="{{ old('tempat') }}" style="margin-top: 10px;">
                        </div>
                    
                        <div class="input-group">
                            <label>Catatan Awal Pengambilan</label>
                            <textarea name="catatan" id="catatan_ambil" rows="3" placeholder="Kondisi awal alat saat diambil di lab...">{{ old('catatan') }}</textarea>
                        </div>
                    
                        <!-- Upload Foto Bukti Pengambilan -->
                        <div class="input-group">
                            <label for="foto-ambil" class="upload-box" id="label-foto-ambil">
                                📷<br>Klik di sini untuk upload foto barang saat diambil di meja lab
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
                        <p>Scan QR Code yang tertempel di <strong>Meja Pengembalian Lab</strong> untuk memverifikasi lokasi</p>
                    </div>

                    <!-- Area Verifikasi QR Code Meja Lab (Pengembalian) -->
                    <div class="qr-box">
                        <label class="qr-label">📍 Scan QR Code Meja Pengembalian Lab</label>
                        <div class="qr-actions">
                            <button type="button" class="btn-scan" id="btn-scan-kembali" onclick="toggleScanner('kembali')">
                                📷 Buka Kamera Scan QR Meja
                            </button>
                            <span class="qr-or">atau</span>
                            <div class="qr-input-group">
                                <input type="text" id="input-kode-kembali" placeholder="Ketik kode meja (SIJALAT-KEMBALI-LAB)">
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
                        <input type="hidden" name="kode_meja" id="kode_meja_kembali" value="">
                        <input type="hidden" name="kode_pinjam" id="kode_pinjam_kembali" value="{{ old('kode_pinjam') }}">

                        <!-- Pilihan Barang yang Sedang Dipinjam -->
                        @if(isset($pinjamanKembali) && $pinjamanKembali->count() > 0)
                            <div class="input-group">
                                <label>Pilih Alat yang Sedang Anda Pinjam:</label>
                                <select id="select_pinjam_kembali" name="pinjam_id" onchange="pilihPeminjaman('kembali', this)" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="">-- Pilih alat yang akan dikembalikan --</option>
                                    @foreach($pinjamanKembali as $p)
                                        <option value="{{ $p->id }}" 
                                                data-barang="{{ $p->alat->nama ?? '' }}" 
                                                data-kode="{{ $p->kode_pinjam }}"
                                                {{ old('pinjam_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->alat->nama ?? 'Alat' }} (Qty: {{ $p->jumlah_minta }}) - Dipinjam: {{ \Carbon\Carbon::parse($p->dipinjam_saat)->format('d M Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    
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
                                <input type="text" name="kelas" id="kelas_kembali" value="{{ old('kelas') }}" placeholder="Contoh: XI SIJA A">
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Nama Barang / Alat yang Dikembalikan</label>
                            <input type="text" name="barang" id="barang_kembali" placeholder="Ketik nama alat yang dikembalikan" value="{{ old('barang', $pinjamanKembali->first()->alat->nama ?? '') }}" required>
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
                                📷<br>Klik di sini untuk upload foto barang saat dikembalikan di meja lab
                            </label>
                            <input type="file" id="foto-kembali" name="bukti_foto" accept="image/*" required onchange="tampilkanNamaFile(this, 'label-foto-kembali')">
                        </div>
                    
                        <button type="submit" class="btn-submit" id="btn-submit-kembali">
                            Submit Bukti Pengembalian
                        </button>
                    </form>
                </div>
            </div>

        </main>

        <!-- ============================================ -->
        <!-- MODAL PREVIEW & CETAK QR CODE MEJA LAB      -->
        <!-- ============================================ -->
        <div id="modal-qr-meja" class="modal-qr" style="display: none;">
            <div class="modal-qr-content">
                <div class="modal-qr-header">
                    <h3>QR Code Meja Lab (Untuk Dicetak & Ditempel)</h3>
                    <button type="button" class="modal-qr-close" onclick="tutupModalQR()">✕</button>
                </div>
                <p style="font-size: 13px; color: #555; margin-bottom: 20px;">
                    Cetak kedua QR Code berikut pada kertas/stiker, lalu tempelkan masing-masing pada meja lab pengambilan dan pengembalian.
                </p>

                <div class="qr-printable-grid">
                    <!-- QR 1: Meja Pengambilan -->
                    <div class="qr-card-print">
                        <div class="qr-card-title">SIJALAT - MEJA PENGAMBILAN</div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=SIJALAT-AMBIL-LAB" alt="QR Meja Pengambilan">
                        <div class="qr-card-code">SIJALAT-AMBIL-LAB</div>
                        <p class="qr-card-desc">Scan QR ini saat mengambil alat di lab</p>
                    </div>

                    <!-- QR 2: Meja Pengembalian -->
                    <div class="qr-card-print">
                        <div class="qr-card-title">SIJALAT - MEJA PENGEMBALIAN</div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=SIJALAT-KEMBALI-LAB" alt="QR Meja Pengembalian">
                        <div class="qr-card-code">SIJALAT-KEMBALI-LAB</div>
                        <p class="qr-card-desc">Scan QR ini saat mengembalikan alat di lab</p>
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: center;">
                    <button type="button" class="btn-submit" style="width: auto; padding: 10px 24px;" onclick="window.print()">
                        🖨️ Cetak Halaman Ini
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('pinjam.create') }}">Peminjaman</a>
                <a href="{{ route('pinjam.bukti') }}">Pengambilan & Pengembalian</a>
                <a>Riwayat</a>
                <a>Report</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script src="{{ asset('js/bukti.js') }}"></script>
    </body>
</html>
