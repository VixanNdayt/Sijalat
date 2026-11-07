<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>QR {{ ucfirst($jenis) }} - {{ $pinjam->kode_pinjam }}</title>

        <link rel="stylesheet" href="{{ asset('css/qr-admin.css') }}">
    </head>
    <body class="qr-print-body">
        @php
            $judul = $jenis === 'ambil' ? 'QR Pengambilan Alat' : 'QR Pengembalian Alat';
            $statusCocok = $jenis === 'ambil'
                ? $pinjam->status === 'disetuji'
                : $pinjam->status === 'dipinjam';
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=340x340&margin=8&data=' . urlencode($kode);
        @endphp

        <div class="qr-print-card">
            <div class="qr-print-brand">SIJALAT</div>
            <h1 class="qr-print-title">{{ $judul }}</h1>
            <p class="qr-print-sub">Tunjukkan QR ini ke siswa untuk di-scan</p>

            <img class="qr-print-image"
                 src="{{ $qrUrl }}"
                 alt="QR {{ $jenis }} {{ $pinjam->kode_pinjam }}">

            <p class="qr-print-code">{{ $kode }}</p>

            <div class="qr-print-details">
                <div>
                    <span>Siswa</span>
                    <span>{{ $pinjam->user->name ?? '-' }} ({{ $pinjam->user->nis ?? '-' }})</span>
                </div>
                <div>
                    <span>Kelas</span>
                    <span>{{ $pinjam->user?->kelas ?: '-' }}</span>
                </div>
                <div>
                    <span>Alat</span>
                    <span>{{ $pinjam->alat->nama ?? '-' }}</span>
                </div>
                <div>
                    <span>Jumlah</span>
                    <span>{{ $pinjam->jumlah_minta }} unit</span>
                </div>
                <div>
                    <span>Kode Pinjam</span>
                    <span>{{ $pinjam->kode_pinjam }}</span>
                </div>
                <div>
                    <span>Ruangan</span>
                    <span>{{ $pinjam->ruangan ?: '-' }}</span>
                </div>
                <div>
                    <span>Jadwal</span>
                    <span>{{ \Carbon\Carbon::parse($pinjam->dipinjam_saat)->format('d M Y, H.i') }} &ndash; {{ \Carbon\Carbon::parse($pinjam->dikembalikan_saat)->format('d M Y, H.i') }}</span>
                </div>
                <div>
                    <span>Status</span>
                    <span>{{ $pinjam->status_label }}</span>
                </div>
            </div>

            @if (! $statusCocok)
                <p class="qr-print-note">
                    QR ini <strong>tidak berlaku</strong> untuk status peminjaman saat ini.
                    @if ($jenis === 'ambil')
                        QR pengambilan hanya bisa dipakai ketika status <strong>Disetujui</strong>.
                    @else
                        QR pengembalian hanya bisa dipakai ketika status <strong>Dipinjam</strong>.
                    @endif
                </p>
            @else
                <p class="qr-print-note" style="background:#ecfdf5; border-color:#a7f3d0; color:#065f46;">
                    QR ini terikat pada transaksi <strong>{{ $pinjam->kode_pinjam }}</strong>.
                    Siswa tidak bisa memakainya untuk transaksi lain.
                </p>
            @endif

            <div class="qr-print-actions no-print">
                <button type="button" onclick="window.print()">
                    Cetak QR
                </button>
                <button type="button" class="secondary" onclick="window.close()">
                    Tutup
                </button>
            </div>
        </div>
    </body>
</html>
