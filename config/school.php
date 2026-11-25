<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daftar kelas
    |--------------------------------------------------------------------------
    |
    | Dipakai pada form registrasi dan validasi server agar pengguna hanya
    | bisa memilih kelas yang memang tersedia di jurusan.
    |
    */
    'classes' => [
        'X SIJA A',
        'X SIJA B',
        'XI SIJA A',
        'XI SIJA B',
        'XII SIJA A',
        'XII SIJA B',
        'XIII SIJA A',
        'XIII SIJA B',
    ],

    /*
    |--------------------------------------------------------------------------
    | Jadwal pengambilan dan pengembalian alat
    |--------------------------------------------------------------------------
    |
    | Pengambilan paling cepat dilakukan pada jam ini, pada tanggal yang
    | tercantum di form peminjaman. Pengembalian paling lambat dilakukan
    | pada jam terakhir pembelajaran sesuai hari yang bersangkutan.
    |
    */
    'jam_ambil_mulai' => '06:30',

    'jam_kembali_terakhir' => [
        1 => '16:30', // Senin
        2 => '16:35', // Selasa
        3 => '16:35', // Rabu
        4 => '16:35', // Kamis
        5 => '14:45', // Jumat
    ],
];
