<!-- Footer bersama -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="Logo SIJALAT">
            <div>
                <strong>SIJALAT</strong>
                <p>Sistem Peminjaman Alat SIJA</p>
            </div>
        </div>

        <nav class="footer-links" aria-label="Tautan footer">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            @if (auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('approval.index') }}">Approval</a>
                <a href="{{ route('admin.qr.index') }}">QR Transaksi</a>
                <a href="{{ route('admin.bukti.index') }}">Bukti Transaksi</a>
                <a href="{{ route('admin.alat.index') }}">Manage Data</a>
                <a href="{{ route('admin.analisis') }}">Analisis</a>
            @else
                <a href="{{ route('pinjam.create') }}">Peminjaman</a>
                <a href="{{ route('pinjam.bukti') }}">Pengambilan &amp; Pengembalian</a>
            @endif
            <a href="{{ route('guide') }}">Panduan</a>
            <a href="{{ route('profile.edit') }}">Profil</a>
        </nav>

        <div class="footer-meta">
            <span>&copy; {{ date('Y') }} SIJALAT</span>
            <span>SIJA &middot; Peminjaman alat jadi lebih mudah</span>
        </div>
    </div>
</footer>
