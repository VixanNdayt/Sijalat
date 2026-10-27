<?php

namespace Database\Seeders;

use App\Models\alat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        alat::create([
            'code' => '02.04.02.08.21',
            'nama' => 'Tang Rivet',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.04.03.01.32',
            'nama' => 'Multimeter',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.06.03',
            'nama' => 'Switch HUB 24 Port',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.02.01',
            'nama' => 'Komputer',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.02.01/1',
            'nama' => 'CPU-AMD',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.02.01/2',
            'nama' => 'CPU-Intel-Pen 3',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.02.02/1',
            'nama' => 'Laptop Acer Travelmate',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.02.02/2',
            'nama' => 'Laptop Acer Aspire',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.09',
            'nama' => 'Bar Code Hand',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.10',
            'nama' => 'Mikrotik RB9312nD',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '01.03.02.05.02.06.018',
            'nama' => 'Power Supply',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.11',
            'nama' => 'AXIOO MyPC Pro K7 (8S1H9-22)',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.12',
            'nama' => 'Router Board RB951G',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.13',
            'nama' => 'Splicer Fiber Optik',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.14',
            'nama' => 'Raspberry PI 4 (Paket Basic)',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.15',
            'nama' => 'SWITCH HP ARUBA 24 PORT 1430 R8R49A',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.05.09-2',
            'nama' => 'LAN Tester 100 ft',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.06.03-2',
            'nama' => 'Switch HUB',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.06.03-2',
            'nama' => 'Switch 24 Port (LGS 124-24 Port)',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.09.03.06.05',
            'nama' => 'Access Point',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.06.02',
            'nama' => 'Wireless Router Indoor (RB-9516)',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.01.05',
            'nama' => 'Cisco Switch 2980 8 TCS',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.01.03',
            'nama' => 'Lan tester Digital',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

         alat::create([
            'code' => '02.06.03.06.06',
            'nama' => 'Tang criping AMP',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.005',
            'nama' => 'Laptop hp',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.006',
            'nama' => 'Laptop Lenovo',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.007',
            'nama' => 'Laptop Asus',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.008',
            'nama' => 'Laptop Axio mybook pro',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.009',
            'nama' => 'Mac book ',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

        alat::create([
            'code' => '01.03.02.05.02.04.010',
            'nama' => 'Projector PANASONIC-VX430',
            'jumlah_stok' => 10,
            'img' => 'equipment/default.jpg',
        ]);

    }
}
