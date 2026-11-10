<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinjamRequestTest extends TestCase
{
    use RefreshDatabase;

    private function buatAlat(): Alat
    {
        return Alat::create([
            'code' => 'ALT-REQ-1',
            'nama' => 'Switch Request',
            'jumlah_stok' => 5,
            'description' => 'Alat untuk pengujian pengajuan peminjaman',
            'jenis' => 'switch',
            'kondisi_baik' => 5,
        ]);
    }

    private function payload(Alat $alat): array
    {
        // 9 November 2026 adalah hari Senin, dalam jam sekolah.
        return [
            'alat_id' => [$alat->id],
            'jumlah_minta' => [2],
            'dipinjam_saat' => '2026-11-09 09:00:00',
            'dikembalikan_saat' => '2026-11-09 10:00:00',
            'ruangan' => 'Lab Jaringan',
            'deskripsi_minta' => 'Praktik konfigurasi jaringan',
        ];
    }

    public function test_pengajuan_dengan_jumlah_yang_tidak_berpasangan_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['jumlah_minta'] = [7 => 1];

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('jumlah_minta');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_tanggal_tidak_valid_ditolak_tanpa_error_server(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = 'bukan-tanggal';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dipinjam_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_tanggal_berbentuk_array_ditolak_tanpa_error_server(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = ['2026-11-09 09:00:00'];

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dipinjam_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_pengajuan_yang_valid_membuat_transaksi_pending(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $this->payload($alat))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('pinjams', [
            'user_id' => $siswa->id,
            'alat_id' => $alat->id,
            'jumlah_minta' => 2,
            'status' => 'pending',
        ]);

        $this->assertSame(1, Pinjam::count());
    }

    public function test_konfirmasi_hapus_pengajuan_aman_dari_nama_alat_berisi_kutip(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();
        $alat->update(['nama' => "Kabel ');alert(document.cookie);//"]);

        Pinjam::create([
            'kode_pinjam' => 'SJL-XSS1',
            'qr_ambil_token' => str_repeat('a', 48),
            'qr_kembali_token' => str_repeat('b', 48),
            'user_id' => $siswa->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->addDay(),
            'dikembalikan_saat' => now()->addDay()->addHour(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($siswa)->get(route('dashboard'));

        $response->assertOk();

        // Nama alat tidak boleh memutus string JavaScript pada atribut onsubmit.
        $response->assertDontSee("peminjaman Kabel ');alert", false);
        $response->assertSee('\\u0027', false);
    }
}
