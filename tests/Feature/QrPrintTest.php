<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrPrintTest extends TestCase
{
    use RefreshDatabase;

    private function buatPinjam(User $student): Pinjam
    {
        $alat = Alat::create([
            'code' => 'ALT-QR-1',
            'nama' => 'Router QR',
            'jumlah_stok' => 1,
            'description' => 'Alat untuk pengujian QR',
            'jenis' => 'router',
            'kondisi_baik' => 1,
        ]);

        return Pinjam::create([
            'kode_pinjam' => 'SJL-' . strtoupper(Str::random(6)),
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now(),
            'dikembalikan_saat' => now()->addHour(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab Jaringan',
            'status' => 'disetuji',
        ]);
    }

    public function test_qr_dirender_lokal_tanpa_mengirim_token_ke_layanan_luar(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $pinjam = $this->buatPinjam($student);

        $response = $this->actingAs($admin)->get(route('admin.qr.print', [
            'pinjam' => $pinjam->id,
            'jenis' => 'ambil',
        ]));

        $response->assertOk()
            ->assertSee('vendor/qrcode-generator.js', false)
            ->assertSee('data-kode="' . $pinjam->kodeQr('ambil') . '"', false)
            ->assertDontSee('api.qrserver.com', false);
    }

    public function test_siswa_tidak_bisa_membuka_halaman_qr(): void
    {
        $student = User::factory()->create();
        $pinjam = $this->buatPinjam($student);

        $this->actingAs($student)
            ->get(route('admin.qr.print', [
                'pinjam' => $pinjam->id,
                'jenis' => 'ambil',
            ]))
            ->assertForbidden();
    }
}
