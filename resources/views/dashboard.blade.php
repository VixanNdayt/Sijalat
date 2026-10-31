<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'SIJALAT - Dashboard')</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

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

            <div class="header-main">
                <div>
                    <h2>Dashboard</h2>
                    <p>Halo, {{ auth()->user()->name }}. Pantau aktivitas peminjaman dan kewajiban anda</p>
                </div>
                <a href="{{ route('pinjam.create') }}">
                    Ajukan Peminjaman
                </a>
            </div>

            <!-- 4Box -->
            <div class="information-box">
                <div class="inner-information">
                    <p>Sedang dipinjam</p>
                    <h2>{{ $sedangDipinjam }}</h2>
                    <p>Kembali pada waktu</p>
                </div>
                <div class="inner-information">
                    <p>Menunggu Approve</p>
                    <h2>{{ $menunggu }}</h2>
                    @if($terbaru)
                        <p>Waktu permintaan : {{ \Carbon\Carbon::parse($terbaru->dipinjam_saat)->format('d M Y, H.i') }}</p>
                    @else
                        <p>Waktu permintaan : Belum ada</p>
                    @endif
                </div>
                <div class="inner-information">
                    <p>Selesai dipinjam</p>
                    <h2>{{ $dikembalikan }}</h2>
                    <p>Presentase tepat waktu</p>
                </div>
                <div class="inner-information">
                    <p>Catatan Pelanggaran</p>
                    <h2>{{ $pelanggaran }}</h2>
                    <p>Terakhir pada</p>
                </div>
            </div>

            <!-- Barang yang sedang dipinjam -->
            <div class="sedang-pinjam-outer">
                @if ($pinjamanAktif->isNotEmpty())
                    @foreach ( $pinjamanAktif as $item)
                        <div class="sedang-dipinjam">
                            <img src="{{ asset('svg/fluent_box-20-regular.svg') }}">
                            <div class="text-sd">
                                <div class="inner-text-sd">
                                    <p>Nama alat : {{ $item->alat->nama }}</p>
                                    @if ($item->status === 'pending')
                                        <p>Pending</p>
                                    @elseif ($item->status === 'dipinjam')
                                        <p>Sedang dipinjam</p>
                                    @endif
                                </div>
                                <div class="inner-text-sd">
                                    <p>{{ $item->alat->code }}</p>
                                    <p>{{ \Carbon\Carbon::parse($item->dipinjam_saat)->format('d M Y, H.i') }}</p>-
                                    <p>{{ \Carbon\Carbon::parse($item->dikembalikan_saat)->format('d M Y, H.i') }}</p>
                                </div>
                            </div>                            
                        </div>
                    @endforeach 
                @endif
            </div>

            <!-- Bottom section -->
            <div class="riwayat">
                <div class="riwayat-peminjaman">
                    <h2>Riwayat Peminjaman</h2>
                    <div class="rp-a">
                        <div class="rp-b"><h5>KODE</h5></div>
                        <div class="rp-b"><h5>ALAT</h5></div>
                        <div class="rp-b"><h5>PINJAM</h5></div>
                        <div class="rp-b"><h5>KEMBALI</h5></div>
                        <div class="rp-b"><h5>KONDISI</h5></div>
                    </div>
                    <div>
                        @forelse ($riwayat as $item)
                            <div class="rp-c1">
                                <div class="rp-c">
                                    {{ $item->alat->code }}
                                </div>
                                <div class="rp-c">
                                    {{ $item->alat->nama }}
                                </div>
                                <div class="rp-c">
                                    {{ \Carbon\Carbon::parse($item->dipinjam_saat)->format('d M Y, H.i') }}
                                </div>
                                <div class="rp-c">
                                   {{  \Carbon\Carbon::parse($item->dikembalikan_saat)->format('d M Y, H.i')  }}
                                </div>
                                <div class="rp-c">
                                    @if ($item->status === 'pending')
                                        <span class="P">Pending</span>
                                    @elseif ($item->status ==='dipinjam')
                                        <span class="sd-a">Sedang dipinjam</span>
                                    @elseif ($item->status === 'dikembalikan') 
                                        <span class="sd-b">Sudah dikembalikan</span>
                                    @else
                                        <span>{{ ucfirst($item->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td>
                                    Belum ada riwayat aktivitas
                                </td>
                            </tr>
                        @endforelse
                    </div>
                </div>
                <div class="riwayat-pelanggaran">
                    <h2>Riwayat Pelanggaran</h2>
                </div>
            </div>
        </main>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>SIJALAT</h2>
                <p>Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('pinjam.create') }}">Peminjaman</a>
                <a>Riwayat</a>
                <a>Report</a>
                <a>Guide</a>
            </div>
        </aside>

        <script src="{{ asset('js/univ.js') }}"></script>
    </body>
</html>