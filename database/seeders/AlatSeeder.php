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
            'code' => 'ALT-001',
            'nama' => 'Multimeter Digital',
            'jumlah_stok' => 10,
            'img' => 'equipment/multimeter.jpg',
        ]);
    }
}
