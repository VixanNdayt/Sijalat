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
            ])
            ->assertRedirect();

        $this->assertSame(8, $alat->fresh()->jumlah_stok);
        $this->assertSame(10, (int) $alat->fresh()->kondisi_baik);
    }
}
