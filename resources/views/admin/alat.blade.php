<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alat-edit.css') }}">

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

            <div class="manage-container">

                <!-- KOLOM KIRI: GRID KATALOG ALAT -->
                <div class="katalog-section">
                    <h2 style="font-size: 20px; margin-bottom: 15px; font-weight: bold;">Katalog Alat Lab</h2>

                    <div class="grid-katalog">
                        @foreach($alats as $alat)
                        <div class="card-alat">
                            <!-- Foto Alat -->
                            <div class="img-wrapper">
                                <img src="{{ asset($alat->img) }}" alt="{{ $alat->nama }}">
                            </div>

                            <!-- Detail Informasi -->
                            <div class="card-body">
                                <h3>{{ $alat->nama }}</h3>
                                <p class="deskripsi">{{ Str::limit($alat->description, 255) }}</p>

                                <!-- Rincian Kondisi -->
                                <div class="kondisi-box">
                                    <span class="badge code">Kode: {{ $alat->code }}</span>
                                    <span class="badge total">Total: {{ $alat->jumlah_stok }}</span>
                                    <span class="badge baik">Baik: {{ $alat->kondisi_baik }}</span>
                                    <span class="badge rusak">Rusak: {{ $alat->kondisi_rusak }}</span>
                                    <span class="badge mtc">Mtc: {{ $alat->kondisi_maintenance }}</span>
                                </div>
                            </div>
                        
                            <!-- Tombol Aksi -->
                            <div class="card-footer">
                                <button type="button" class="btn-edit" onclick="editAlat({{ $alat }})">✏️ Edit</button>
                                <form action="{{ route('admin.alat.destroy', $alat->id) }}" method="POST" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-delete" onclick="return confirm('Hapus alat ini?')">🗑️ Hapus</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            
                <!-- KOLOM KANAN: FORM TAMBAH / EDIT -->
                <div class="form-section">
                    <div class="form-card">
                        <h3 id="formTitle">Tambah Alat Baru</h3>

                        @if ($errors->any())
                            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f87171;">
                                <strong>Gagal Menyimpan Data!</strong>
                                <ul style="margin-top: 5px; margin-bottom: 0;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form id="formAlat" action="{{ route('admin.alat.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <!-- Method PUT disembunyikan, hanya aktif saat mode Edit -->
                            <input type="hidden" name="_method" id="formMethod" value="POST">

                            <div class="input-group">
                                <label>Kode Alat</label>
                                <input type="text" name="code" id="inputCode" placeholder="Contoh: ALAT-001" required>
                            </div>
                        
                            <div class="input-group">
                                <label>Nama Alat</label>
                                <input type="text" name="nama" id="inputNama" required>
                            </div>
                        
                            <div class="input-group">
                                <label>Deskripsi</label>
                                <textarea name="description" id="inputDeskripsi" rows="3" required></textarea>
                            </div>
                        
                            <!-- Input 3 Kondisi Sejajar -->
                            <label>Rincian Qty (Kondisi)</label>
                            <div class="row-kondisi">
                                <div class="col-kondisi">
                                    <span style="font-size:12px; color:#16a34a;">Baik</span>
                                    <input type="number" name="kondisi_baik" id="inputBaik" value="0" min="0" required>
                                </div>
                                <div class="col-kondisi">
                                    <span style="font-size:12px; color:#dc2626;">Rusak</span>
                                    <input type="number" name="kondisi_rusak" id="inputRusak" value="0" min="0" required>
                                </div>
                                <div class="col-kondisi">
                                    <span style="font-size:12px; color:#ca8a04;">Mtc</span>
                                    <input type="number" name="kondisi_maintenance" id="inputMtc" value="0" min="0" required>
                                </div>
                            </div>
                            <p style="font-size: 11px; color: #888; margin-bottom: 15px;">Total Qty akan dihitung otomatis.</p>
                        
                            <div class="input-group">
                                <label>Foto Alat</label>
                                <input type="file" name="img" id="inputFoto" accept="image/*">
                                <small id="fotoHelp">Kosongkan jika tidak ingin mengubah foto (saat edit).</small>
                            </div>
                        
                            <div style="display: flex; gap:10px; margin-top: 20px;">
                                <button type="submit" class="btn-submit" id="btnSubmit">Simpan Alat</button>
                                <button type="button" class="btn-reset" onclick="resetForm()" style="display:none;" id="btnBatal">Batal Edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            
            </div>
        </main>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('approval.index') }}">Approval</a>
                <a href="{{ route('admin.alat.index') }}">Manage Data</a>
                <a href="{{ route('admin.analisis') }}">Analisis</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/manage.js') }}"></script>
    </body>
</html>
