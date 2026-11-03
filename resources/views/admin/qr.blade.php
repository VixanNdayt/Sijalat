<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - QR Transaksi</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/qr-admin.css') }}">
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="">
                    <p>{{ auth()->user()->name }} </p>
                </a>

                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            <div class="qr-admin-container">
                <div class="qr-admin-header">
                    <h2>QR Transaksi Peminjaman</h2>
                    <p>Tampilkan QR di bawah ini ke siswa. Setiap QR hanya berlaku untuk satu transaksi.</p>
                </div>

                <div class="qr-info-banner">
                    <x-icon name="qr-code" size="20" />
                    <div>
                        <strong>Alur baru:</strong> siswa membuka halaman <em>Pengambilan &amp; Pengembalian</em>,
                        lalu men-scan QR yang Anda tampilkan. QR pengambilan hanya berlaku saat status
                        <strong>Disetujui</strong>, dan QR pengembalian hanya berlaku saat status <strong>Dipinjam</strong>.
                    </div>
                </div>

                <form class="qr-search" method="GET" action="{{ route('admin.qr.index') }}">
                    <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama siswa, NIS, kode pinjam, atau alat...">
                    <button type="submit" class="btn-primary">
                        <x-icon name="search" size="15" /> Cari
                    </button>
                </form>

                <div class="qr-grid">
                    @forelse ($pinjams as $pinjam)
                        @php
                            $bisaAmbil = $pinjam->status === 'disetuji';
                            $bisaKembali = $pinjam->status === 'dipinjam';
                        @endphp

                        <div class="qr-card">
                            <div class="qr-card-head">
                                <div>
                                    <h3>{{ $pinjam->user->name ?? 'Siswa' }}</h3>
                                    <small>NIS {{ $pinjam->user->nis ?? '-' }} &middot; {{ $pinjam->kode_pinjam }}</small>
                                </div>
                                <span class="qr-status {{ $pinjam->status }}">
                                    {{ $pinjam->status === 'disetuji' ? 'Disetujui' : ucfirst($pinjam->status) }}
                                </span>
                            </div>

                            <div class="qr-meta">
                                <div>
                                    <span>Alat</span>
                                    <span>{{ $pinjam->alat->nama ?? '-' }}</span>
                                </div>
                                <div>
                                    <span>Kelas</span>
                                    <span>{{ $pinjam->user->kelas ?: '-' }}</span>
                                </div>
                                <div>
                                    <span>Jumlah</span>
                                    <span>{{ $pinjam->jumlah_minta }} unit</span>
                                </div>
                                <div>
                                    <span>Ruangan</span>
                                    <span>{{ $pinjam->ruangan ?: '-' }}</span>
                                </div>
                                <div>
                                    <span>Jadwal pinjam</span>
                                    <span>{{ \Carbon\Carbon::parse($pinjam->dipinjam_saat)->format('d M Y, H.i') }}</span>
                                </div>
                            </div>

                            <div class="qr-actions">
                                @if ($bisaAmbil)
                                    <a class="btn-qr ambil" target="_blank" rel="noopener"
                                       href="{{ route('admin.qr.print', ['pinjam' => $pinjam->id, 'jenis' => 'ambil']) }}">
                                        <x-icon name="qr-code" size="15" /> QR Pengambilan
                                    </a>
                                @else
                                    <span class="btn-qr disabled">
                                        <x-icon name="qr-code" size="15" /> Pengambilan selesai
                                    </span>
                                @endif

                                @if ($bisaKembali)
                                    <a class="btn-qr kembali" target="_blank" rel="noopener"
                                       href="{{ route('admin.qr.print', ['pinjam' => $pinjam->id, 'jenis' => 'kembali']) }}">
                                        <x-icon name="qr-code" size="15" /> QR Pengembalian
                                    </a>
                                @else
                                    <span class="btn-qr disabled">
                                        <x-icon name="qr-code" size="15" /> Belum dipinjam
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="qr-empty">
                            <x-icon name="inbox" size="30" />
                            <p>
                                @if ($cari !== '')
                                    Tidak ada transaksi yang cocok dengan "{{ $cari }}".
                                @else
                                    Belum ada transaksi aktif. Setujui pengajuan peminjaman terlebih dahulu di halaman Approval.
                                @endif
                            </p>
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
