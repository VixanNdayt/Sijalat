<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

                <!-- Alert Notifikasi Error / Sukses -->
                @if (session('error'))
                    <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div style="background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Tombol Tab -->
                <div class="tab-container">
                    <button type="button" id="btn-ambil" class="tab-btn active" onclick="switchTab('ambil')">PENGAMBILAN ALAT</button>
                    <button type="button" id="btn-kembali" class="tab-btn" onclick="switchTab('kembali')">PENGEMBALIAN ALAT</button>
                </div>
            
                <!-- BUNGKUSAN FORM PENGAMBILAN -->
                <div id="form-ambil" class="form-wrapper">
                    <div class="form-header">
                        <h3>Data Pengambilan Alat</h3>
                        <p>Lengkapi data dan upload bukti foto awal pengambilan</p>
                    </div>
                
                    <form action="{{ route('transaksi.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <!-- Hidden input aksi agar Controller tahu ini form apa -->
                        <input type="hidden" name="aksi" value="AMBIL">
                    
                        <div class="input-group">
                            <label>Nama</label>
                            <input type="text" name="nama" value="{{ old('nama', auth()->user()->name) }}" placeholder="Masukkan nama" required>
                        </div>
                    
                        <div class="row">
                            <div class="input-group col">
                                <label>NIS</label>
                                <input type="text" name="nis" value="{{ old('nis', auth()->user()->nis) }}" placeholder="Masukkan NIS" required>
                            </div>
                            <div class="input-group col">
                                <label>Kelas</label>
                                <input type="text" name="kelas" value="{{ old('kelas') }}" placeholder="Contoh: XI SIJA A">
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Barang (Sesuai Pengajuan)</label>
                            <input type="text" name="barang" placeholder="Ketik nama alat, misal: Tang Rivet" value="{{ old('barang') }}" required>
                        </div>
                    
                        <div class="input-group">
                            <label>Waktu dan Tempat</label>
                            <div class="row">
                                <input type="time" name="waktu_mulai" class="col" value="{{ old('waktu_mulai') }}">
                                <input type="time" name="waktu_selesai" class="col" value="{{ old('waktu_selesai') }}">
                            </div>
                            <input type="text" name="tempat" placeholder="Lokasi penggunaan (misal: Ruang Lab 2)" value="{{ old('tempat') }}" style="margin-top: 10px;">
                        </div>
                    
                        <div class="input-group">
                            <label>Catatan / Note</label>
                            <textarea name="catatan" rows="3" placeholder="Kondisi alat atau keperluan tambahan...">{{ old('catatan') }}</textarea>
                        </div>
                    
                        <!-- Desain Tombol Upload Gambar Kotak -->
                        <div class="input-group">
                            <label for="foto-ambil" class="upload-box" id="label-foto-ambil">
                                📷<br>Klik di sini untuk upload foto bukti awal pengambilan
                            </label>
                            <input type="file" id="foto-ambil" name="bukti_foto" accept="image/*" required onchange="tampilkanNamaFile(this, 'label-foto-ambil')">
                        </div>
                    
                        <button type="submit" class="btn-submit">Submit Pengambilan</button>
                    </form>
                </div>
            
                <!-- BUNGKUSAN FORM PENGEMBALIAN (Awalnya Disembunyikan) -->
                <div id="form-kembali" class="form-wrapper" style="display: none;">
                    <div class="form-header">
                        <h3>Data Pengembalian Alat</h3>
                        <p>Lengkapi data dan upload bukti foto kondisi akhir pengembalian</p>
                    </div>
                
                    <form action="{{ route('transaksi.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="aksi" value="KEMBALI">
                    
                        <div class="input-group">
                            <label>Nama</label>
                            <input type="text" name="nama" value="{{ old('nama', auth()->user()->name) }}" placeholder="Masukkan nama" required>
                        </div>
                    
                        <div class="row">
                            <div class="input-group col">
                                <label>NIS</label>
                                <input type="text" name="nis" value="{{ old('nis', auth()->user()->nis) }}" placeholder="Masukkan NIS" required>
                            </div>
                            <div class="input-group col">
                                <label>Kelas</label>
                                <input type="text" name="kelas" value="{{ old('kelas') }}" placeholder="Contoh: XI SIJA A">
                            </div>
                        </div>
                    
                        <div class="input-group">
                            <label>Barang (Sesuai Pengajuan)</label>
                            <input type="text" name="barang" placeholder="Ketik nama alat yang dikembalikan" value="{{ old('barang') }}" required>
                        </div>
                    
                        <div class="input-group">
                            <label>Waktu Pengembalian</label>
                            <input type="time" name="waktu_kembali" value="{{ old('waktu_kembali') }}" style="width: 50%;">
                        </div>
                    
                        <div class="input-group">
                            <label>Catatan Akhir Pengembalian</label>
                            <textarea name="catatan" rows="3" placeholder="Kondisi alat saat dikembalikan...">{{ old('catatan') }}</textarea>
                        </div>
                    
                        <div class="input-group">
                            <label for="foto-kembali" class="upload-box" id="label-foto-kembali">
                                📷<br>Klik di sini untuk upload foto bukti akhir pengembalian
                            </label>
                            <input type="file" id="foto-kembali" name="bukti_foto" accept="image/*" required onchange="tampilkanNamaFile(this, 'label-foto-kembali')">
                        </div>
                    
                        <button type="submit" class="btn-submit">Submit Pengembalian</button>
                    </form>
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
                <a href="{{ route('pinjam.bukti') }}">Pengambilan & Pengembalian</a>
                <a>Riwayat</a>
                <a>Report</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/bukti.js') }}"></script>
    </body>
</html>
