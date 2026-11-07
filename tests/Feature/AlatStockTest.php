<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlatStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_alat_tidak_mengembalikan_stok_yang_sedang_dipinjam(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-STOK-1',
            'nama' => 'Switch Stok',
            'jumlah_stok' => 8,
            'description' => 'Alat untuk pengujian stok',
            'jenis' => 'switch',
            'kondisi_baik' => 10,
            'kondisi_pengecekan' => 0,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        Pinjam::create([
            'kode_pinjam' => 'SJL-' . strtoupper(Str::random(6)),
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $siswa->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subHour(),
            'dikembalikan_saat' => now()->addHour(),
            'jumlah_minta' => 2,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'dipinjam',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.alat.update', $alat->id), [
                'nama' => 'Switch Stok',
                'description' => 'Alat untuk pengujian stok',
                'code' => 'ALT-STOK-1',
                'kondisi_baik' => 10,
                'kondisi_pengecekan' => 0,
                'kondisi_rusak' => 0,
                'kondisi_maintenance' => 0,
                'jenis' => 'switch',
            ])
            ->assertRedirect();

        $this->assertSame(8, $alat->fresh()->jumlah_stok);
        $this->assertSame(10, (int) $alat->fresh()->kondisi_baik);
    }

    public function test_halaman_admin_alat_menampilkan_input_jenis(): void
    {
        $admin = User::factory()->admin()->create();

        Alat::create([
            'code' => 'ALT-JENIS-1',
            'nama' => 'Switch Jenis',
            'jumlah_stok' => 1,
            'description' => 'Alat untuk pengujian form jenis',
            'jenis' => 'switch',
            'kondisi_baik' => 1,
            'kondisi_pengecekan' => 0,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.alat.index'))
            ->assertOk()
            ->assertSee('name="jenis"', false)
            ->assertSee('id="inputJenis"', false)
            ->assertSee('Pilih jenis alat');
    }

    public function test_update_alat_menolak_jenis_di_luar_daftar(): void
    {
        $admin = User::factory()->admin()->create();

        $alat = Alat::create([
            'code' => 'ALT-JENIS-2',
            'nama' => 'Router Jenis',
            'jumlah_stok' => 2,
            'description' => 'Alat untuk pengujian validasi jenis',
            'jenis' => 'router',
            'kondisi_baik' => 2,
            'kondisi_pengecekan' => 0,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.alat.update', $alat->id), [
                'nama' => 'Router Jenis',
                'description' => 'Alat untuk pengujian validasi jenis',
                'code' => 'ALT-JENIS-2',
                'kondisi_baik' => 2,
                'kondisi_pengecekan' => 0,
                'kondisi_rusak' => 0,
                'kondisi_maintenance' => 0,
                'jenis' => 'local_area_network',
            ])
            ->assertSessionHasErrors('jenis');

        $this->assertSame('router', $alat->fresh()->jenis);
    }

    public function test_alat_dengan_riwayat_peminjaman_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-HIST-1',
            'nama' => 'Router Riwayat',
            'jumlah_stok' => 3,
            'description' => 'Alat yang sudah pernah dipinjam',
            'jenis' => 'router',
            'kondisi_baik' => 3,
            'kondisi_pengecekan' => 0,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        Pinjam::create([
            'kode_pinjam' => 'SJL-' . strtoupper(Str::random(6)),
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $siswa->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subDays(2),
            'dikembalikan_saat' => now()->subDay(),
            'dikembalikan_aktual' => now()->subDay(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'dikembalikan',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.alat.destroy', $alat->id))
            ->assertRedirect()
            ->assertSessionHasErrors('hapus');

        $this->assertDatabaseHas('alats', ['id' => $alat->id]);
        $this->assertDatabaseHas('pinjams', ['alat_id' => $alat->id]);
    }
}
