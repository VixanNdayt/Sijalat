<!-- Sidebar siswa -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="" width="36" height="36">
            <span>SIJALAT</span>
        </a>
        <p>Menu Siswa</p>
    </div>
    <div class="sidebar-menu">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('pinjam.create') }}">Peminjaman</a>
        <a href="{{ route('pinjam.bukti') }}">Pengambilan &amp; Pengembalian</a>
        <a href="{{ route('guide') }}">Guide</a>
    </div>
</aside>
