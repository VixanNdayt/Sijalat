<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_not_access_admin_routes(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get('/admin/alat')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/admin/analisis')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/alat')
            ->assertOk();
    }

    public function test_halaman_approval_menampilkan_guru_dan_mapel(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-APR-1',
            'nama' => 'Access Point Approval',
            'jumlah_stok' => 2,
            'description' => 'Alat untuk pengujian halaman approval',
            'jenis' => 'switch',
            'kondisi_baik' => 2,
        ]);

        $guru = Guru::create(['nama' => 'Guru Approval']);
        $mapel = Mapel::create(['nama_mapel' => 'Mapel Approval']);

        Pinjam::create([
            'kode_pinjam' => 'SJL-APR01',
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'dipinjam_saat' => now()->addHour(),
            'dikembalikan_saat' => now()->addHours(2),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get('/admin/approval')
            ->assertOk()
            ->assertSee('Guru Approval')
            ->assertSee('Mapel Approval')
            ->assertSee('Access Point Approval');
    }

    public function test_parameter_filter_berbentuk_array_tidak_menyebabkan_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/analisis?periode[]=harian')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/qr?cari[]=router')
            ->assertOk();
    }
}
