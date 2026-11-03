<!-- Sidebar guru/admin -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('svg/logo-sijalat.svg') }}" alt="" width="36" height="36">
            <span>SIJALAT</span>
        </a>
        <p>Menu Guru</p>
    </div>
    <div class="sidebar-menu">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('approval.index') }}">Approval</a>
        <a href="{{ route('admin.qr.index') }}">QR Transaksi</a>
        <a href="{{ route('admin.alat.index') }}">Manage Data</a>
        <a href="{{ route('admin.analisis') }}">Analisis</a>
        <a href="{{ route('guide') }}">Guide</a>
    </div>
</aside>
