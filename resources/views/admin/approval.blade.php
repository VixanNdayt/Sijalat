<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/approval.css') }}">

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
                
                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
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
                        <a href="{{ route('admin.qr.index') }}" style="font-weight:bold; color:#065f46; margin-left:6px;">Lihat QR transaksi &rarr;</a>
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
                        <span style="text-align: center;">Aksi</span>
                    </div>
                    
                    @forelse($pendingList as $index => $trx)
                    <div class="table-row">
                        <span>{{ $index + 1 }}</span>
                        <span>
                            <strong>{{ $trx->user?->name ?? 'Pengguna tidak tersedia' }}</strong><br>
                            <small style="color: #666;">NIS: {{ $trx->user?->nis ?? '-' }} &middot; Kelas: {{ $trx->user?->kelas ?: '-' }}</small>
                        </span>
                        <span>{{ $trx->alat?->nama ?? 'Alat tidak tersedia' }}</span>
                        <span>{{ $trx->jumlah_minta }} unit</span>
                        <span>{{ $trx->deskripsi_minta }}</span>
                        <span>{{ \Carbon\Carbon::parse($trx->dipinjam_saat)->format('d M Y, H:i') }}</span>
                        
                        <div class="action-buttons">
                            <!-- Tombol Setuju -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="setuju">
                                <button type="submit" class="btn-approve" onclick="return confirm('Setujui peminjaman ini?')"><x-icon name="check" size="15" /> Setuju</button>
                            </form>
                        
                            <!-- Tombol Tolak -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="tolak">
                                <button type="submit" class="btn-reject" onclick="return confirm('Tolak peminjaman ini?')"><x-icon name="x" size="15" /> Tolak</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div style="padding: 20px; text-align: center; color: #666; background: white; border-radius: 8px;">
                        <x-icon name="inbox" size="26" />
                        <p style="margin:8px 0 0;">Tidak ada antrean peminjaman saat ini.</p>
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