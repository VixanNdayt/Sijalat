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
    'sekolah' => 'SMK Negeri 2 Yogyakarta',

    'jam_layanan' => [
        ['hari' => 'Senin', 'jam' => '08.00 - 16.30'],
        ['hari' => 'Selasa - Kamis', 'jam' => '07.00 - 16.35'],
        ['hari' => 'Jumat', 'jam' => '08.00 - 14.45'],
        ['hari' => 'Sabtu - Minggu', 'jam' => 'Tutup'],
    ],

    'kontak' => [
        [
            'peran' => 'Kepala Laboratorium',
            'nama' => 'Penanggung Jawab Laboratorium SIJA',
            'email' => 'kepala.lab@stembayo.sch.id',
        ],
        [
            'peran' => 'Laboran / Toolman',
            'nama' => 'Ruang Alat SIJA',
            'email' => 'toolman.sija@stembayo.sch.id',
        ],
        [
            'peran' => 'Ketua Program Keahlian',
            'nama' => 'Kaprog SIJA',
            'email' => 'kaprog.sija@stembayo.sch.id',
        ],
    ],
];
