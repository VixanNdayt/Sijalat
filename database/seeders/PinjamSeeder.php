<?php

namespace Database\Seeders;

use App\Models\pinjam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PinjamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         pinjam::create([
            'user_id'=> '1',
            'alat_id' => '1',
            'dipinjam_saat' => '2026-10-25 12:10:02',
            'dikembalikan_saat' => '2026-10-25 13:10:02',
            'jumlah_minta' => '20',
            'deskripsi_minta' => 'buat praktik',
            'status' => 'pending',
        ]);
    }
}
