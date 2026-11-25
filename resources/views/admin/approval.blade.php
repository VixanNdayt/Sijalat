<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}?v=3">
        <link rel="stylesheet" href="{{ asset('css/approval.css') }}">
        <link rel="stylesheet" href="{{ asset('css/csp.css') }}">

        @stack('styles')
        @include('partials.theme-head')
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
                @include('partials.navbar-actions')
            </nav>

            <div class="admin-container">
                <div class="header-teks">
                    <h2>Daftar Persetujuan (Pending)</h2>
                    <p>Berikan persetujuan untuk siswa yang mengajukan peminjaman alat.</p>
                </div>
            
                <!-- Notifikasi Sukses -->
                @if(session('success'))
                    <div class="alert-success">
                        {{ session('success') }}
                        <a href="{{ route('admin.qr.index') }}" class="u-link-inline">Lihat QR transaksi &rarr;</a>
                    </div>
                @endif
                
                <div class="table-container">
                    <div class="table-header">
                        <span>No</span>
                        <span>Nama Siswa</span>
                        <span>Barang</span>
                        <span>Qty</span>
                        <span>Alasan</span>
                        <span>Waktu Pinjam</span>
                        <span class="u-text-center">Aksi</span>
                    </div>
                    
                    @forelse($pendingList as $index => $trx)
                    <div class="table-row">
                        <span>{{ $index + 1 }}</span>
                        <span>
                            <strong>{{ $trx->user?->name ?? 'Pengguna tidak tersedia' }}</strong><br>
                            <small class="u-text-muted">NIS: {{ $trx->user?->nis ?? '-' }} &middot; Kelas: {{ $trx->user?->kelas ?: '-' }}</small>
                        </span>
                        <span>
                            {{ $trx->alat?->nama ?? 'Alat tidak tersedia' }}
                            @if ($trx->guru || $trx->mapel)
                                <br>
                                <small class="u-text-muted">
                                    Guru: {{ $trx->guru?->nama ?? '-' }} &middot; Mapel: {{ $trx->mapel?->nama_mapel ?? '-' }}
                                </small>
                            @endif
                        </span>
                        <span>{{ $trx->jumlah_minta }} unit</span>
                        <span>{{ $trx->deskripsi_minta }}</span>
                        <span>{{ \Carbon\Carbon::parse($trx->dipinjam_saat)->format('d M Y, H:i') }}</span>
                        
                        <div class="action-buttons">
                            <!-- Tombol Setuju -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="setuju">
                                <button type="submit" class="btn-approve" data-confirm="Setujui peminjaman ini?"><x-icon name="check" size="15" /> Setuju</button>
                            </form>
                        
                            <!-- Tombol Tolak -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="tolak">
                                <button type="submit" class="btn-reject" data-confirm="Tolak peminjaman ini?"><x-icon name="x" size="15" /> Tolak</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="u-empty-box">
                        <x-icon name="inbox" size="26" />
                        <p class="u-empty-text">Tidak ada antrean peminjaman saat ini.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @include('partials.footer')
        </main>

        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
    </body>
</html>