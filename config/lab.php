<?php

/*
|--------------------------------------------------------------------------
| Informasi Laboratorium SIJALAT
|--------------------------------------------------------------------------
|
| Nilai di sini dipakai oleh homepage publik (jam layanan, kontak, dan
| tautan penting). Sesuaikan nama/email dengan data resmi sekolah.
|
*/

return [
    'jurusan' => 'Sistem Informasi Jaringan dan Aplikasi',
    'sekolah' => 'SMK Negeri 2 Depok Sleman',

    /*
    | Pengambilan alat paling cepat pukul 06.30. Pengembalian paling lambat
    | pada jam terakhir pembelajaran sesuai hari. Ubah jam di sini bila
    | jadwal sekolah berubah.
    */
    'jam_layanan' => [
        ['hari' => 'Senin', 'jam' => '06.30 - 16.30'],
        ['hari' => 'Selasa - Kamis', 'jam' => '06.30 - 16.35'],
        ['hari' => 'Jumat', 'jam' => '06.30 - 14.45'],
        ['hari' => 'Sabtu - Minggu', 'jam' => 'Tutup'],
    ],

    'kontak' => [
        [
            'peran' => 'Developer',
            'nama' => 'Dzikri N',
            'email' => 'dzikrinurhi@gmail.com',
        ],
    ],
];
