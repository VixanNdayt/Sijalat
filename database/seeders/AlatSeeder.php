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
            'code' => 'ALT-004',
            'nama' => 'Multimeter Digital',
            'jumlah_stok' => 10,
            'img' => 'equipment/multimeter.jpg',
        ]);

        alat::create([
            'code' => 'ALT-005',
            'nama' => 'TPlink AP ',
            'jumlah_stok' => 20,
            'img' => 'equipment/tplink.jpg',
        ]);
  
        alat::create([
            'code' => 'ALT-006',
            'nama' => 'Mikrotik HapLite',
            'jumlah_stok' => 26,
            'img' => 'equipment/mikrotik-hap.jpg',
        ]);
   
        alat::create([
            'code' => 'ALT-007',
            'nama' => 'LAN cable',
            'jumlah_stok' => 100,
            'img' => 'equipment/lan-cab.jpg',
        ]);
 
        alat::create([
            'code' => 'ALT-008',
            'nama' => 'LAN tester',
            'jumlah_stok' => 50,
            'img' => 'equipment/lan-tes.jpg',
        ]);
   
        alat::create([
            'code' => 'ALT-009',
            'nama' => 'Crimping tool',
            'jumlah_stok' => 10,
            'img' => 'equipment/crimping-tool.jpg',
        ]);

        alat::create([
            'code' => 'ALT-010',
            'nama' => 'Obeng ',
            'jumlah_stok' => 40,
            'img' => 'equipment/obeng.jpg',
        ]);

        alat::create([
            'code' => 'ALT-011',
            'nama' => 'Fiber optic splicer',
            'jumlah_stok' => 36,
            'img' => 'equipment/fib-op.jpg',
        ]);

        alat::create([
            'code' => 'ALT-012',
            'nama' => 'HDMI cable',
            'jumlah_stok' => 26,
            'img' => 'equipment/hdmi-cab.jpg',
        ]);

    }
}
