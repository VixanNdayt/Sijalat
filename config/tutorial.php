<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tutorial peminjaman di homepage
    |--------------------------------------------------------------------------
    |
    | Setiap langkah ditampilkan sebagai baris bergantian (gambar kiri/kanan)
    | mengikuti wireframe. Simpan screenshot langkah di folder
    | public/tutorial/ dengan nama file yang tertera pada "image".
    |
    */

    'image_path' => 'tutorial',

    'steps' => [
        [
            'image' => '01-daftar.jpg',
            'title' => 'Buka situs dan klik Daftar',
            'description' => 'Akses halaman utama SIJALAT, lalu klik tombol Daftar di kanan atas untuk membuat akun siswa baru.',
            'note' => null,
        ],
        [
            'image' => '02-registrasi.jpg',
            'title' => 'Lengkapi formulir pendaftaran',
            'description' => 'Isi nama lengkap, NIS, kelas, email sekolah, password, dan konfirmasi password. Gunakan email @student.stembayo.sch.id dengan password minimal 10 karakter berisi huruf besar, huruf kecil, dan angka.',
            'note' => 'Pastikan NIS dan email aktif agar akun bisa dipakai untuk peminjaman.',
        ],
        [
            'image' => '03-menu-profil.jpg',
            'title' => 'Buka menu profil dan pilih Dashboard',
            'description' => 'Setelah akun berhasil dibuat, klik nama atau avatar di kanan atas, lalu pilih Dashboard untuk masuk ke halaman utama siswa.',
            'note' => null,
        ],
        [
            'image' => '04-ajukan.jpg',
            'title' => 'Klik Ajukan Peminjaman',
            'description' => 'Di dashboard siswa, klik tombol Ajukan Peminjaman untuk membuka katalog alat yang tersedia.',
            'note' => null,
        ],
        [
            'image' => '05-pilih-alat.jpg',
            'title' => 'Cari dan tambahkan alat',
            'description' => 'Gunakan kolom pencarian atau filter jenis alat. Tentukan jumlah pada kartu alat, lalu klik Tambahkan untuk memasukkannya ke keranjang.',
            'note' => null,
        ],
        [
            'image' => '06-keranjang.jpg',
            'title' => 'Periksa keranjang peminjaman',
            'description' => 'Pastikan daftar alat dan jumlahnya sudah benar pada panel Keranjang di sisi kanan.',
            'note' => null,
        ],
        [
            'image' => '07-form-peminjaman.jpg',
            'title' => 'Isi form peminjaman',
            'description' => 'Lengkapi guru pengampu, mata pelajaran, waktu ambil dan kembali, ruangan, serta keterangan keperluan. Pengambilan paling cepat pukul 06.30 pada tanggal yang dipilih, dan pengembalian paling lambat pada jam terakhir pembelajaran.',
            'note' => 'Klik Submit setelah semua data terisi untuk mengirim pengajuan ke guru atau admin.',
        ],
        [
            'image' => '08-menu-siswa.jpg',
            'title' => 'Buka menu Pengambilan & Pengembalian',
            'description' => 'Setelah pengajuan disetujui, buka menu siswa melalui ikon garis tiga, lalu pilih Pengambilan & Pengembalian.',
            'note' => null,
        ],
        [
            'image' => '09-pengambilan.jpg',
            'title' => 'Scan QR dan unggah bukti pengambilan',
            'description' => 'Pada tab Pengambilan, scan atau tempel kode QR pengambilan dari guru, lalu unggah foto bukti pengambilan alat dan klik Submit Bukti Pengambilan.',
            'note' => null,
        ],
        [
            'image' => '10-pengembalian.jpg',
            'title' => 'Scan QR dan unggah bukti pengembalian',
            'description' => 'Pada tab Pengembalian, scan QR pengembalian, pilih kondisi alat (Baik, Butuh Pengecekan, atau Rusak), unggah foto bukti, lalu klik Submit Bukti Pengembalian.',
            'note' => 'Pengembalian setelah jam terakhir pembelajaran tidak dapat diproses dan tercatat sebagai pelanggaran.',
        ],
        [
            'image' => '11-selesai.jpg',
            'title' => 'Pengembalian selesai',
            'description' => 'Jika berhasil, muncul konfirmasi pengembalian. Status transaksi berubah menjadi Dikembalikan dan struk dapat dibuka melalui tombol Lihat Struk.',
            'note' => null,
        ],
    ],
];
