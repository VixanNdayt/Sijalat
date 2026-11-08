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
                    <x-user-avatar :user="auth()->user()" />
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
                    <div class="manage-heading">
                        <h2>Katalog Alat Lab</h2>
                        <p aria-live="polite"><span id="alatCount">{{ $alats->count() }} alat terdaftar</span></p>
                    </div>

                    <div class="manage-actions">
                        <div class="catalog-search">
                            <x-icon name="search" size="16" />
                            <input type="search" id="cariAlat" placeholder="Cari nama atau kode alat..." aria-label="Cari alat" autocomplete="off">
                        </div>

                        <button type="button" class="btn-primary" onclick="modeTambahAlat()">
                            <x-icon name="plus" size="16" /> Tambah Alat
                        </button>
                    </div>
                </div>

                <!-- GRID KATALOG ALAT -->
                <div class="grid-katalog" id="gridKatalog">
                    @forelse($alats as $alat)
                    <article class="card-alat"
                             data-cari="{{ Str::lower($alat->nama . ' ' . $alat->code . ' ' . $alat->description . ' ' . $alat->jenis) }}">
                        <!-- Foto Alat -->
                        <div class="img-wrapper">
                            @if ($alat->img)
                                <img src="{{ asset($alat->img) }}" alt="{{ $alat->nama }}" loading="lazy" decoding="async">
                            @else
                                <span class="img-empty"><x-icon name="box" size="30" /></span>
                            @endif

                            <span class="stock-pill {{ $alat->jumlah_stok > 0 ? 'is-ready' : 'is-empty' }}">
                                {{ $alat->jumlah_stok > 0 ? 'Tersedia' : 'Habis' }}
                            </span>
                        </div>

                        <!-- Detail Informasi -->
                        <div class="card-body">
                            <h3 title="{{ $alat->nama }}">{{ $alat->nama }}</h3>
                            <p class="code-line">Kode <span>{{ $alat->code }}</span></p>
                            <p class="code-line">Lokasi <span>{{ $alat->lokasiLabel() }}</span></p>
                            <p class="deskripsi">{{ Str::limit($alat->description, 120) }}</p>

                            <!-- Rincian Kondisi -->
                            <div class="stok-ringkas">
                                <div class="stok-total">
                                    <strong>{{ $alat->jumlah_stok }}</strong>
                                    <span>unit tersedia</span>
                                </div>

                                <div class="kondisi-grid">
                                    <span class="kondisi-item"><i class="dot baik"></i>Baik <b>{{ $alat->kondisi_baik }}</b></span>
                                    <span class="kondisi-item"><i class="dot cek"></i>Cek <b>{{ $alat->kondisi_pengecekan }}</b></span>
                                    <span class="kondisi-item"><i class="dot rusak"></i>Rusak <b>{{ $alat->kondisi_rusak }}</b></span>
                                    <span class="kondisi-item"><i class="dot mtc"></i>Mtc <b>{{ $alat->kondisi_maintenance }}</b></span>
                                </div>
                            </div>
                        </div>
                    
                        <!-- Tombol Aksi -->
                        <div class="card-footer">
                            <button type="button" class="btn-edit" onclick="editAlat({{ $alat->toJson() }})">
                                <x-icon name="pencil" size="15" /> Edit
                            </button>
                            <form action="{{ route('admin.alat.destroy', $alat->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Hapus alat ini? Tindakan ini tidak bisa dibatalkan.')">
                                    <x-icon name="trash" size="15" /> Hapus
                                </button>
                            </form>
                        </div>
                    </article>
                    @empty
                    <div class="katalog-kosong">
                        <x-icon name="box" size="28" />
                        <p>Belum ada alat. Klik <strong>Tambah Alat</strong> untuk mulai mengisi katalog.</p>
                    </div>
                    @endforelse

                    @if ($alats->isNotEmpty())
                    <div class="katalog-kosong" id="katalogKosong" hidden>
                        <x-icon name="search" size="28" />
                        <p>Tidak ada alat yang cocok dengan pencarian.</p>
                    </div>
                    @endif
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
                            <label for="inputLokasi">Lokasi Penyimpanan</label>
                            <input type="text" name="lokasi" id="inputLokasi" placeholder="Contoh: Lab Jaringan - Rak A1" value="{{ old('lokasi') }}">
                            <small class="form-hint">Tampil pada kartu katalog di beranda.</small>
                        </div>
                    
                        <div class="input-group">
                            <label for="inputDeskripsi">Deskripsi</label>
                            <textarea name="description" id="inputDeskripsi" rows="3" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="input-group">
                            <label for="inputJenis">Jenis Alat</label>
                            <select name="jenis" id="inputJenis" required data-custom-select>
                                <option value="" disabled {{ old('jenis') ? '' : 'selected' }}>Pilih jenis alat</option>
                                @foreach ($jenisOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('jenis') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint">Dipakai sebagai filter kategori di halaman peminjaman.</small>
                        </div>
                    
                        <!-- Input 4 Kondisi Sejajar -->
                        <label class="kondisi-label">Rincian Qty (Kondisi)</label>
                        <div class="row-kondisi">
                            <div class="col-kondisi">
                                <span class="kondisi-caption baik">Baik</span>
                                <input type="number" name="kondisi_baik" id="inputBaik" value="{{ old('kondisi_baik', 0) }}" min="0" inputmode="numeric" required>
                            </div>
                            <div class="col-kondisi">
                                <span class="kondisi-caption cek">Pengecekan</span>
                                <input type="number" name="kondisi_pengecekan" id="inputCek" value="{{ old('kondisi_pengecekan', 0) }}" min="0" inputmode="numeric" required>
                            </div>
                            <div class="col-kondisi">
                                <span class="kondisi-caption rusak">Rusak</span>
                                <input type="number" name="kondisi_rusak" id="inputRusak" value="{{ old('kondisi_rusak', 0) }}" min="0" inputmode="numeric" required>
                            </div>
                            <div class="col-kondisi">
                                <span class="kondisi-caption mtc">Mtc</span>
                                <input type="number" name="kondisi_maintenance" id="inputMtc" value="{{ old('kondisi_maintenance', 0) }}" min="0" inputmode="numeric" required>
                            </div>
                        </div>
                        <p class="form-hint">Stok tersedia dihitung dari total kondisi dikurangi unit yang sedang dipinjam.</p>
                    
                        <div class="input-group">
                            <label for="inputFoto">Foto Alat</label>
                            <input type="file" name="img" id="inputFoto" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                            <small id="fotoHelp">Format JPG/PNG, maksimal 2 MB. Kosongkan jika tidak ingin mengubah foto saat edit.</small>
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
        <script src="{{ asset('js/custom-select.js') }}?v=2"></script>
    </body>
</html>
