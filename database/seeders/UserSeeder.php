<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Akun contoh di bawah memakai password default yang mudah ditebak.
        // Jangan pernah membuatnya di server produksi/staging yang bisa diakses
        // publik. Buat akun admin produksi secara manual dengan password kuat.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn(
                'UserSeeder dilewati karena APP_ENV bukan local/testing. '
                . 'Buat akun admin produksi secara manual dengan password kuat.'
            );

            return;
        }

        User::create([
            'name' => 'User2',
            'nis' => '2100',
            'kelas' => 'XI SIJA A',
            'email' => 'user2@test.com',
            'password' => bcrypt('anu202002'),
        ]);

        User::create([
            'name' => 'User3',
            'nis' => '2120',
            'kelas' => 'XI SIJA B',
            'email' => 'user3@test.com',
            'password' => bcrypt('anu202002'),
        ]);

        $admin = User::create([
            'name' => 'Admin1',
            'nis'  => '1',
            'email' => 'admin1@admin.com',
            'password' => bcrypt('admin123'),
        ]);

        // Role tidak termasuk mass-assignable; naikkan hak akses secara eksplisit.
        $admin->forceFill(['role_user' => 'admin'])->save();
    }
}
