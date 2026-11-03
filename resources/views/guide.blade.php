<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - Panduan</title>

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/guide.css') }}">
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

            <div class="guide-container">
                <header class="guide-hero">
                    <span class="guide-badge">
                        <x-icon name="{{ $isAdmin ? 'clipboard' : 'user' }}" size="14" />
                        {{ $isAdmin ? 'Panduan Guru / Admin' : 'Panduan Siswa' }}
                    </span>

                    @if ($isAdmin)
                        <h1>Kelola peminjaman alat dengan SIJALAT</h1>
                        <p>
                            Mulai dari menyetujui pengajuan, menampilkan QR transaksi ke siswa,
                            sampai memantau stok dan statistik peminjaman.
                        </p>
                        <div class="guide-hero-actions">
                            <a class="guide-btn" href="{{ route('approval.index') }}">
                                <x-icon name="clipboard" size="16" /> Buka Approval
                            </a>
                            <a class="guide-btn ghost" href="{{ route('admin.qr.index') }}">
                                <x-icon name="qr-code" size="16" /> QR Transaksi
                            </a>
                        </div>
                    @else
                        <h1>Cara meminjam alat lewat SIJALAT</h1>
                        <p>
                            Ajukan peminjaman, ambil alat dengan scan QR dari guru,
                            lalu kembalikan alat dengan QR pengembalian.
                        </p>
                        <div class="guide-hero-actions">
                            <a class="guide-btn" href="{{ route('pinjam.create') }}">
                                <x-icon name="cart" size="16" /> Ajukan Peminjaman
                            </a>
                            <a class="guide-btn ghost" href="{{ route('pinjam.bukti') }}">
                                <x-icon name="qr-code" size="16" /> Scan QR
                            </a>
                        </div>
                    @endif
                </header>

                <section class="guide-section">
                    <h2>{{ $isAdmin ? 'Alur kerja guru' : 'Alur peminjaman siswa' }}</h2>

                    <div class="guide-steps">
                        @if ($isAdmin)
                            <article class="guide-step">
                                <span class="guide-step-num">1</span>
                                <div>
                                    <h3>Periksa pengajuan siswa</h3>
                                    <p>
                                        Buka menu <strong>Approval</strong>, cek nama siswa, kelas, alat,
                                        jumlah, jadwal, dan keperluan. Tekan <strong>Setujui</strong> atau
                                        <strong>Tolak</strong>. Stok belum berkurang di tahap ini.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">2</span>
                                <div>
                                    <h3>Tampilkan QR pengambilan</h3>
                                    <p>
                                        Buka menu <strong>QR Transaksi</strong>, pilih transaksi yang sudah
                                        disetujui, lalu tekan <strong>QR Pengambilan</strong>. Tampilkan atau
                                        cetak QR tersebut untuk di-scan siswa. Setiap QR hanya berlaku untuk
                                        satu transaksi.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">3</span>
                                <div>
                                    <h3>Awasi pengembalian alat</h3>
                                    <p>
                                        Setelah siswa mengambil alat, status berubah menjadi
                                        <strong>Dipinjam</strong> dan tombol <strong>QR Pengembalian</strong>
                                        aktif. Tampilkan QR itu saat siswa mengembalikan alat.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">4</span>
                                <div>
                                    <h3>Kelola data dan pantau statistik</h3>
                                    <p>
                                        Gunakan menu <strong>Manage Data</strong> untuk menambah, mengubah,
                                        atau menghapus alat beserta stoknya. Menu <strong>Analisis</strong>
                                        menampilkan alat yang paling sering dipinjam.
                                    </p>
                                </div>
                            </article>
                        @else
                            <article class="guide-step">
                                <span class="guide-step-num">1</span>
                                <div>
                                    <h3>Ajukan peminjaman</h3>
                                    <p>
                                        Buka menu <strong>Peminjaman</strong>, pilih alat, isi jumlah, jadwal,
                                        ruangan, dan keperluan, lalu kirim pengajuan. Status awal adalah
                                        <strong>Pending</strong>.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">2</span>
                                <div>
                                    <h3>Tunggu persetujuan guru</h3>
                                    <p>
                                        Pantau status di <strong>Dashboard</strong>. Selama masih
                                        <strong>Pending</strong>, pengajuan bisa dibatalkan lewat tombol
                                        <strong>Hapus</strong>. Setelah <strong>Disetujui</strong>, alat siap
                                        diambil sesuai jadwal.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">3</span>
                                <div>
                                    <h3>Ambil alat dengan scan QR</h3>
                                    <p>
                                        Buka <strong>Pengambilan &amp; Pengembalian</strong>, pilih tab
                                        Pengambilan, lalu scan <strong>QR Pengambilan</strong> yang ditampilkan
                                        guru. Kelas, waktu, dan lokasi terisi otomatis. Upload foto bukti, lalu
                                        tekan <strong>Submit Bukti Pengambilan</strong>.
                                    </p>
                                </div>
                            </article>

                            <article class="guide-step">
                                <span class="guide-step-num">4</span>
                                <div>
                                    <h3>Kembalikan alat</h3>
                                    <p>
                                        Saat mengembalikan, buka tab Pengembalian dan scan
                                        <strong>QR Pengembalian</strong> dari guru. Upload foto kondisi alat,
                                        lalu submit. Status peminjaman akan selesai.
                                    </p>
                                </div>
                            </article>
                        @endif
                    </div>
                </section>

                <section class="guide-section">
                    <h2>Hal yang perlu diperhatikan</h2>

                    <ul class="guide-notes">
                        @if ($isAdmin)
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                QR pengambilan hanya berlaku saat status <strong>Disetujui</strong>, dan QR
                                pengembalian hanya berlaku saat status <strong>Dipinjam</strong>.
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                Setiap QR terikat pada satu transaksi dan tidak bisa dipakai ulang setelah
                                transaksi selesai.
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                Stok alat baru berkurang ketika siswa berhasil submit bukti pengambilan, dan
                                bertambah kembali saat pengembalian selesai.
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                Halaman QR hanya bisa dibuka oleh guru/admin. Pastikan siswa sudah mengisi
                                kelas di profilnya agar form terisi otomatis.
                            </li>
                        @else
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                QR dari guru hanya bisa dipakai untuk transaksi milik Anda sendiri dan harus
                                sesuai jenisnya (pengambilan atau pengembalian).
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                QR pengambilan tidak berlaku jika pengajuan belum disetujui atau alat sudah
                                diambil. QR pengembalian baru aktif setelah status <strong>Dipinjam</strong>.
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                Jika kamera tidak bisa dibuka, tempel kode QR secara manual pada kolom yang
                                tersedia, lalu tekan <strong>Verifikasi</strong>.
                            </li>
                            <li>
                                <x-icon name="alert-triangle" size="16" />
                                Kelas diisi otomatis dari profil. Jika salah atau kosong, perbaiki lewat
                                halaman <strong>Profil</strong>.
                            </li>
                        @endif
                    </ul>
                </section>

                <section class="guide-section">
                    <h2>Pertanyaan umum</h2>

                    @if ($isAdmin)
                        <details class="guide-faq">
                            <summary>Kenapa tombol QR pengembalian tidak bisa diklik?</summary>
                            <p>
                                Tombol tersebut hanya aktif ketika status transaksi sudah
                                <strong>Dipinjam</strong>. Setelah siswa berhasil scan QR pengambilan,
                                status akan berubah otomatis.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>Apakah siswa bisa memakai QR milik transaksi lain?</summary>
                            <p>
                                Tidak. Sistem memeriksa pemilik transaksi, jenis QR, dan token acak. QR
                                transaksi lain akan ditolak.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>Gambar QR tidak muncul saat halaman dibuka?</summary>
                            <p>
                                Gambar QR dibuat lewat layanan online, jadi butuh koneksi internet. Kode QR
                                tetap tercetak sebagai teks dan bisa diketik manual oleh siswa.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>Kapan stok alat berkurang?</summary>
                            <p>
                                Stok berkurang saat siswa berhasil mengirim bukti pengambilan, bukan saat
                                pengajuan disetujui. Stok kembali bertambah setelah pengembalian selesai.
                            </p>
                        </details>
                    @else
                        <details class="guide-faq">
                            <summary>Bagaimana cara membatalkan pengajuan?</summary>
                            <p>
                                Buka <strong>Dashboard</strong>, cari pengajuan berstatus
                                <strong>Pending</strong>, lalu tekan tombol <strong>Hapus</strong>. Pengajuan
                                yang sudah disetujui tidak bisa dihapus.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>QR dari guru ditolak, kenapa?</summary>
                            <p>
                                Pastikan QR tersebut memang untuk transaksi Anda, jenisnya sesuai
                                (pengambilan/pengembalian), dan status peminjaman sudah tepat. QR juga tidak
                                bisa dipakai dua kali.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>Kamera tidak bisa scan QR, bagaimana?</summary>
                            <p>
                                Beri izin kamera pada browser, atau gunakan kolom
                                <strong>Tempel/ketik kode QR dari guru</strong> lalu tekan
                                <strong>Verifikasi</strong>.
                            </p>
                        </details>
                        <details class="guide-faq">
                            <summary>Kelas di form salah atau kosong?</summary>
                            <p>
                                Perbaiki lewat halaman <strong>Profil</strong> (ikon nama di kanan atas).
                                Setelah disimpan, kelas akan terisi otomatis pada transaksi berikutnya.
                            </p>
                        </details>
                    @endif
                </section>

                <div class="guide-help">
                    <x-icon name="inbox" size="20" />
                    <p>
                        Masih bingung? Hubungi guru atau petugas SIJA saat jam kerja.
                        @if ($isAdmin)
                            Pastikan setiap transaksi diproses lewat menu <strong>QR Transaksi</strong> agar
                            keamanan data tetap terjaga.
                        @else
                            Selalu minta guru menampilkan QR langsung dari halaman QR Transaksi, jangan
                            memakai screenshot milik siswa lain.
                        @endif
                    </p>
                </div>
            </div>
            @include('partials.footer')
        </main>

        @if ($isAdmin)
            @include('partials.sidebar-admin')
        @else
            @include('partials.sidebar-siswa')
        @endif

        <script src="{{ asset('js/univ.js') }}"></script>
    </body>
</html>
