<?php

namespace Database\Seeders;

use App\Models\Alat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Alat::create([
            'code' => '02.04.02.08.21',
            'nama' => 'Tang Rivet',
            'description' => 'Digunakan untuk memasang paku keling (rivet) guna menggabungkan dua lembar plat atau material tipis.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Tang Rivet.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.04.03.01.32',
            'nama' => 'Multimeter',
            'description' => 'Alat ukur elektronika untuk mengukur tegangan (volt), hambatan (ohm), dan arus listrik (ampere).',
            'jumlah_stok' => 10,
            'img' => 'equipment/Multimeter.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.06.03.06.03',
            'nama' => 'Switch HUB 24 Port',
            'description' => 'Perangkat jaringan untuk menghubungkan banyak komputer dalam satu jaringan LAN dengan 24 port.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Switch HUB 24 Port.png',
            'jenis' => 'switch'
        ]);

        Alat::create([
            'code' => '02.09.03.02.01',
            'nama' => 'Komputer',
            'jumlah_stok' => 10,
            'description' => 'Computer yang sudah siap digunakan',
            'img' => 'equipment/Komputer.png',
            'jenis' => 'bagian_pc'
        ]);

        Alat::create([
            'code' => '02.09.03.02.01/1',
            'nama' => 'CPU-AMD',
            'description' => 'Unit pemprosesan utama berbasis arsitektur AMD untuk menjalankan sistem dan aplikasi komputer.',
            'jumlah_stok' => 10,
            'img' => 'equipment/CPU-AMD.png',
            'jenis' => 'bagian_pc'
        ]);

        Alat::create([
            'code' => '02.09.03.02.01/2',
            'nama' => 'CPU-Intel-Gen 3',
            'description' => 'Unit pemprosesan utama generasi lama berbasis Intel Pentium 3 untuk komputer.',
            'jumlah_stok' => 10,
            'img' => 'equipment/CPU-Intel-Gen 3.png',
            'jenis' => 'bagian_pc'
        ]);

        Alat::create([
            'code' => '02.09.03.02.02/1',
            'nama' => 'Laptop Acer Travelmate',
            'description' => 'Komputer jinjing portabel merk Acer seri Travelmate untuk kebutuhan kerja mobile.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop Acer Travelmate.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '02.09.03.02.02/2',
            'nama' => 'Laptop Acer Aspire',
            'description' => 'Komputer jinjing portabel merk Acer seri Aspire untuk penggunaan umum sehari-hari.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop Acer Aspire.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '02.09.03.05.09',
            'nama' => 'Barcode Hand',
            'description' => 'Pemindai genggam untuk membaca kode batang (barcode) pada produk atau aset.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Barcode Hand.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.09.03.05.10',
            'nama' => 'Mikrotik RB9312nD',
            'description' => 'Routerboard kecil untuk mengatur lalu lintas data dan manajemen jaringan internet.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Mikrotik RB9312nD.png',
            'jenis' => 'router'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.06.018',
            'nama' => 'Power Supply',
            'description' => 'Komponen yang memasok daya listrik arus searah ke komponen internal komputer.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Power Supply.png',
            'jenis' => 'bagian_pc'
        ]);

        Alat::create([
            'code' => '02.09.03.05.11',
            'nama' => 'AXIOO MyPC Pro K7 (8S1H9-22)',
            'description' => 'Komputer All-in-One merk Axioo untuk mendukung produktivitas kerja atau pembelajaran.',
            'jumlah_stok' => 10,
            'img' => 'equipment/AXIOO MyPC Pro K7 (8S1H9-22).png',
            'jenis' => 'bagian_pc'
        ]);

        Alat::create([
            'code' => '02.09.03.05.12',
            'nama' => 'Routerboard RB951G',
            'description' => 'Perangkat router gigabit untuk routing jaringan, firewall, dan manajemen bandwidth.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Routerboard RB951G.png',
            'jenis' => 'router'
        ]);

        Alat::create([
            'code' => '02.09.03.05.13',
            'nama' => 'Splicer Fiber Optik',
            'description' => 'Alat untuk menyambungkan dua ujung serat optik secara presisi dengan peleburan panas.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Splicer Fiber Optik.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.09.03.05.14',
            'nama' => 'Raspberry PI 4 (Paket Basic)',
            'description' => 'Komputer papan tunggal (single-board computer) untuk pemrograman, server mini, dan proyek IoT.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Raspberry PI 4 (Paket Basic).png',
            'jenis' => 'IoT'
        ]);

        Alat::create([
            'code' => '02.09.03.05.15',
            'nama' => 'SWITCH HP ARUBA 24 PORT 1430 R8R49A',
            'description' => 'Switch managed/unmanaged HP Aruba dengan 24 port untuk konektivitas jaringan enterprise.',
            'jumlah_stok' => 10,
            'img' => 'equipment/SWITCH HP ARUBA 24 PORT 1430 R8R49A.png',
            'jenis' => 'switch'
        ]);

        Alat::create([
            'code' => '02.09.03.05.09-2',
            'nama' => 'LAN Tester 100 ft',
            'description' => 'Alat penguji koneksi kabel LAN/UTP dengan jangkauan panjang.',
            'jumlah_stok' => 10,
            'img' => 'equipment/LAN Tester 100 ft.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.09.03.06.03-2',
            'nama' => 'Switch HUB',
            'description' => 'Perangkat penghubung antar komputer dalam jaringan lokal berbasis kabel.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Switch HUB.png',
            'jenis' => 'switch'
        ]);

        Alat::create([
            'code' => '02.06.03.06.03-2',
            'nama' => 'Switch 24 Port (LGS 124-24 Port)',
            'jumlah_stok' => 10,
            'img' => 'equipment/Switch 24 Port (LGS 124-24 Port).png',
            'jenis' => 'switch',
            'description' => 'Switch Linksys 24 port untuk memperluas jaringan lokal berbasis kabel Ethernet.'
        ]);

        Alat::create([
            'code' => '02.09.03.06.05',
            'nama' => 'Access Point',
            'description' => 'Perangkat yang memancarkan sinyal Wi-Fi untuk menghubungkan perangkat nirkabel ke jaringan lokal.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Access Point.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.06.03.06.02',
            'nama' => 'Wireless Router Indoor (RB-9516)',
            'description' => 'Router indoor nirkabel untuk distribusi sinyal Wi-Fi dan manajemen jaringan di dalam ruangan.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Wireless Router Indoor (RB-9516).png',
            'jenis' => 'router'
        ]);

        Alat::create([
            'code' => '02.06.03.01.05',
            'nama' => 'Cisco Switch 2980 8 TCS',
            'description' => 'Switch Cisco kelas enterprise untuk pengaturan dan pembagian jaringan tingkat lanjut.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Cisco Switch 2980 8 TCS.png',
            'jenis' => 'switch'
        ]);

        Alat::create([
            'code' => '02.06.03.01.03',
            'nama' => 'Lan tester Digital',
            'description' => 'Alat penguji kabel UTP/LAN dengan indikator digital untuk mendeteksi kabel putus atau salah susun.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Lan tester Digital.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '02.06.03.06.06',
            'nama' => 'Tang crimping AMP',
            'description' => 'Alat untuk mengupas kabel UTP dan mengunci konektor RJ45 ke ujung kabel.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Tang crimping AMP.png',
            'jenis' => 'alat_jaringan'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.005',
            'nama' => 'Laptop hp',
            'description' => 'Komputer jinjing merk HP untuk komputasi portabel.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop hp.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.006',
            'nama' => 'Laptop Lenovo',
            'description' => 'Komputer jinjing merk Lenovo untuk produktivitas dan tugas harian.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop Lenovo.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.007',
            'nama' => 'Laptop Asus',
            'description' => 'Komputer jinjing merk Asus untuk penggunaan umum maupun multimedia.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop Asus.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.008',
            'nama' => 'Laptop Axio mybook pro',
            'description' => 'Komputer jinjing lokal merk Axioo seri MyBook Pro untuk kegiatan belajar dan kerja.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Laptop Axio mybook pro.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.009',
            'nama' => 'Macbook ',
            'description' => 'Komputer jinjing buatan Apple dengan sistem operasi macOS.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Macbook.png',
            'jenis' => 'laptop'
        ]);

        Alat::create([
            'code' => '01.03.02.05.02.04.010',
            'nama' => 'Projector PANASONIC-VX430',
            'description' => 'Alat proyeksi untuk menampilkan layar komputer atau video ke dinding/layar putih besar.',
            'jumlah_stok' => 10,
            'img' => 'equipment/Projector PANASONIC-VX430.png',
            'jenis' => 'alat_jaringan'
        ]);
    }
}
