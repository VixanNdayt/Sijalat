<!-- Sidebar guru/admin -->
@php
    $unreadChat = \App\Models\Message::unreadCountFor(auth()->user());
@endphp

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
        <a class="{{ request()->routeIs('admin.bukti.*') ? 'active' : '' }}" href="{{ route('admin.bukti.index') }}">Bukti Transaksi</a>
        <a href="{{ route('admin.alat.index') }}">Manage Data</a>
        <a href="{{ route('admin.analisis') }}">Analisis</a>
        <a class="{{ request()->routeIs('admin.chat.*') ? 'active' : '' }}" href="{{ route('admin.chat.index') }}">
            Chat Siswa
            @if ($unreadChat > 0)
                <span class="menu-badge">{{ $unreadChat > 99 ? '99+' : $unreadChat }}</span>
            @endif
        </a>
        <a href="{{ route('guide') }}">Guide</a>
    </div>
</aside>
