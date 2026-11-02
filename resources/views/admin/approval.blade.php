<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                
                <h2>SIJALAT</h2>

                <button class="toggle-btn" id="toggleBtn">☰</button>
            </nav>

            <div class="admin-container">
                <div class="header-teks">
                    <h2>Daftar Persetujuan (Pending)</h2>
                    <p>Berikan persetujuan untuk siswa yang mengajukan peminjaman alat lab.</p>
                </div>
            
                <!-- Notifikasi Sukses -->
                @if(session('success'))
                    <div class="alert-success">
                        {{ session('success') }}
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
                            <strong>{{ $trx->user->name }}</strong><br>
                            <small style="color: #666;">NIS: {{ $trx->user->nis }}</small>
                        </span>
                        <span>{{ $trx->alat->nama }}</span>
                        <span>{{ $trx->jumlah_minta }} unit</span>
                        <span>{{ $trx->deskripsi_minta }}</span>
                        <span>{{ \Carbon\Carbon::parse($trx->dipinjam_saat)->format('d M Y, H:i') }}</span>
                        
                        <div class="action-buttons">
                            <!-- Tombol Setuju -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="setuju">
                                <button type="submit" class="btn-approve" onclick="return confirm('Setujui peminjaman ini?')">✔️ Setuju</button>
                            </form>
                        
                            <!-- Tombol Tolak -->
                            <form action="{{ route('approval.proses', $trx->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="aksi" value="tolak">
                                <button type="submit" class="btn-reject" onclick="return confirm('Tolak peminjaman ini?')">✖️ Tolak</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div style="padding: 20px; text-align: center; color: #666; background: white; border-radius: 8px;">
                        🎉 Tidak ada antrean peminjaman saat ini.
                    </div>
                    @endforelse
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
    </body>
</html>