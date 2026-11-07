<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
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
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="">
                    <p>{{ auth()->user()->name }} </p>
                </a>
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            @if (session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('hapus'))
                <div class="flash flash-error">{{ $errors->first('hapus') }}</div>
            @endif

            <div class="manage-container">

                <!-- TOOLBAR KATALOG -->
                <div class="manage-toolbar">
                    <div>
                        <h2>Katalog Alat Lab</h2>
                        <p>{{ $alats->count() }} alat terdaftar</p>
                    </div>

                    <button type="button" class="btn-primary" onclick="modeTambahAlat()">
                        <x-icon name="plus" size="16" /> Tambah Alat
                    </button>
                </div>

                <!-- GRID KATALOG ALAT -->
                <div class="grid-katalog">
                    @forelse($alats as $alat)
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
                                <span class="badge cek">Cek: {{ $alat->kondisi_pengecekan }}</span>
                                <span class="badge rusak">Rusak: {{ $alat->kondisi_rusak }}</span>
                                <span class="badge mtc">Mtc: {{ $alat->kondisi_maintenance }}</span>
                            </div>
                        </div>
                    
                        <!-- Tombol Aksi -->
                        <div class="card-footer">
                            <button type="button" class="btn-edit" onclick="editAlat({{ $alat->toJson() }})">
                                <x-icon name="pencil" size="15" /> Edit
                            </button>
                            <form action="{{ route('admin.alat.destroy', $alat->id) }}" method="POST" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Hapus alat ini?')">
                                    <x-icon name="trash" size="15" /> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="katalog-kosong">
                        <x-icon name="box" size="28" />
                        <p>Belum ada alat. Klik <strong>Tambah Alat</strong> untuk mulai mengisi katalog.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @include('partials.footer')
        </main>

        <!-- ============================================ -->
        <!-- MODAL TAMBAH / EDIT ALAT                     -->
        <!-- ============================================ -->
        <div class="modal" id="modal-alat" role="dialog" aria-modal="true" aria-labelledby="formTitle">
            <div class="modal-backdrop" data-modal-close></div>

            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 id="formTitle">Tambah Alat Baru</h3>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
                        <x-icon name="x" size="18" />
                    </button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && ! $errors->has('hapus'))
                        <div class="flash flash-error" style="width:100%; margin:0 0 16px;">
                            <strong>Gagal menyimpan data!</strong>
                            <ul style="margin:6px 0 0; padding-left:18px;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="formAlat"
                          action="{{ route('admin.alat.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          data-store-url="{{ route('admin.alat.store') }}"
                          data-update-url="{{ url('admin/alat') }}"
                          data-open-on-load="{{ $errors->any() && ! $errors->has('hapus') ? '1' : '0' }}">
                        @csrf
                        <!-- Method PUT disembunyikan, hanya aktif saat mode Edit -->
                        <input type="hidden" name="_method" id="formMethod" value="{{ old('_method', 'POST') }}">
                        <input type="hidden" name="alat_id" id="inputAlatId" value="{{ old('alat_id') }}">

                        <div class="input-group">
                            <label for="inputCode">Kode Alat</label>
                            <input type="text" name="code" id="inputCode" placeholder="Contoh: ALAT-001" value="{{ old('code') }}" required>
                        </div>
                    
                        <div class="input-group">
                            <label for="inputNama">Nama Alat</label>
                            <input type="text" name="nama" id="inputNama" value="{{ old('nama') }}" required>
                        </div>
                    
                        <div class="input-group">
                            <label for="inputDeskripsi">Deskripsi</label>
                            <textarea name="description" id="inputDeskripsi" rows="3" required>{{ old('description') }}</textarea>
                        </div>
                    
                        <!-- Input 4 Kondisi Sejajar -->
                        <label>Rincian Qty (Kondisi)</label>
                        <div class="row-kondisi">
                            <div class="col-kondisi">
                                <span style="font-size:12px; color:#16a34a;">Baik</span>
                                <input type="number" name="kondisi_baik" id="inputBaik" value="{{ old('kondisi_baik', 0) }}" min="0" required>
                            </div>
                            <div class="col-kondisi">
                                <span style="font-size:12px; color:#0369a1;">Pengecekan</span>
                                <input type="number" name="kondisi_pengecekan" id="inputCek" value="{{ old('kondisi_pengecekan', 0) }}" min="0" required>
                            </div>
                            <div class="col-kondisi">
                                <span style="font-size:12px; color:#dc2626;">Rusak</span>
                                <input type="number" name="kondisi_rusak" id="inputRusak" value="{{ old('kondisi_rusak', 0) }}" min="0" required>
                            </div>
                            <div class="col-kondisi">
                                <span style="font-size:12px; color:#ca8a04;">Mtc</span>
                                <input type="number" name="kondisi_maintenance" id="inputMtc" value="{{ old('kondisi_maintenance', 0) }}" min="0" required>
                            </div>
                        </div>
                        <p style="font-size: 11px; color: #888; margin-bottom: 15px;">Total Qty akan dihitung otomatis dari seluruh kondisi.</p>
                    
                        <div class="input-group">
                            <label for="inputFoto">Foto Alat</label>
                            <input type="file" name="img" id="inputFoto" accept="image/*">
                            <small id="fotoHelp">Kosongkan jika tidak ingin mengubah foto (saat edit).</small>
                        </div>
                    
                        <div class="modal-actions">
                            <button type="submit" class="btn-submit" id="btnSubmit">Simpan Alat</button>
                            <button type="button" class="btn-reset" id="btnBatal" onclick="resetForm()" style="display:none;">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/manage.js') }}"></script>
    </body>
</html>
