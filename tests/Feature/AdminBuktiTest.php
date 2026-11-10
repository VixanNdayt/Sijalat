<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminBuktiTest extends TestCase
{
    use RefreshDatabase;

    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        );
    }

    private function buatAlat(string $kode = 'ALT-BUKTI-1', string $nama = 'Router Bukti'): Alat
    {
        return Alat::create([
            'code' => $kode,
            'nama' => $nama,
            'jumlah_stok' => 2,
            'description' => 'Alat untuk pengujian bukti transaksi',
            'jenis' => 'router',
            'kondisi_baik' => 2,
        ]);
    }

    private function buatPinjam(User $user, Alat $alat, array $atribut = []): Pinjam
    {
        return Pinjam::create(array_merge([
            'kode_pinjam' => 'SJL-' . strtoupper(Str::random(6)),
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $user->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subDay(),
            'dikembalikan_saat' => now(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab Jaringan',
            'status' => 'dipinjam',
        ], $atribut));
    }

    public function test_admin_bisa_melihat_daftar_bukti_transaksi(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create(['name' => 'Budi Santoso']);
        $alat = $this->buatAlat();
        $pinjam = $this->buatPinjam($siswa, $alat);

        $path = 'bukti_transaksi/ambil-' . $pinjam->id . '.png';
        Storage::disk('local')->put($path, $this->pngBytes());
        $pinjam->update(['bukti_ambil' => $path]);

        $response = $this->actingAs($admin)->get(route('admin.bukti.index'));

        $response->assertOk()
            ->assertSee('Bukti Pengambilan')
            ->assertSee('Budi Santoso')
            ->assertSee('Router Bukti')
            ->assertSee($pinjam->kode_pinjam)
            ->assertSee('Bukti ambil')
            ->assertSee(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'ambil']), false);
    }

    public function test_siswa_tidak_bisa_mengakses_halaman_atau_file_bukti(): void
    {
        Storage::fake('local');

        $siswa = User::factory()->create();
        $alat = $this->buatAlat();
        $pinjam = $this->buatPinjam($siswa, $alat, [
            'bukti_ambil' => 'bukti_transaksi/ambil.png',
        ]);

        $this->actingAs($siswa)
            ->get(route('admin.bukti.index'))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'ambil']))
            ->assertForbidden();
    }

    public function test_admin_bisa_membuka_file_bukti_dan_file_hilang_menghasilkan_404(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();
        $pinjam = $this->buatPinjam($siswa, $alat);

        $path = 'bukti_transaksi/kembali-' . $pinjam->id . '.png';
        Storage::disk('local')->put($path, $this->pngBytes());
        $pinjam->update(['bukti_kembali' => $path]);

        $this->actingAs($admin)
            ->get(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'kembali']))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        Storage::disk('local')->delete($path);

        $this->actingAs($admin)
            ->get(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'kembali']))
            ->assertNotFound();

        // Path yang mencoba keluar dari folder bukti juga harus ditolak.
        $pinjam->forceFill(['bukti_kembali' => 'bukti_transaksi/../.env'])->save();

        $this->actingAs($admin)
            ->get(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'kembali']))
            ->assertNotFound();
    }

    public function test_pencarian_dan_filter_jenis_bukti(): void
    {
        $admin = User::factory()->admin()->create();

        $budi = User::factory()->create(['name' => 'Budi Santoso']);
        $siti = User::factory()->create(['name' => 'Siti Aminah']);

        $pinjamAmbil = $this->buatPinjam($budi, $this->buatAlat('ALT-BUDI', 'Switch Budi'), [
            'bukti_ambil' => 'bukti_transaksi/budi.png',
        ]);

        $pinjamKembali = $this->buatPinjam($siti, $this->buatAlat('ALT-SITI', 'Router Siti'), [
            'bukti_kembali' => 'bukti_transaksi/siti.png',
            'status' => 'dikembalikan',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bukti.index', ['cari' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');

        $this->actingAs($admin)
            ->get(route('admin.bukti.index', ['cari' => $pinjamKembali->kode_pinjam]))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('admin.bukti.index', ['jenis' => 'ambil']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');

        $this->actingAs($admin)
            ->get(route('admin.bukti.index', ['jenis' => 'kembali']))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');
    }

    public function test_parameter_pencarian_berbentuk_array_tidak_menyebabkan_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.bukti.index', [
                'jenis' => ['ambil'],
                'cari' => ['Budi'],
            ]))
            ->assertOk();
    }

    public function test_file_bukti_lama_di_disk_publik_tetap_bisa_dibuka_admin(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();
        $pinjam = $this->buatPinjam($siswa, $alat);

        $path = 'bukti_transaksi/legacy-' . $pinjam->id . '.png';
        Storage::disk('public')->put($path, $this->pngBytes());
        $pinjam->update(['bukti_ambil' => $path]);

        $this->actingAs($admin)
            ->get(route('admin.bukti.foto', ['pinjam' => $pinjam->id, 'jenis' => 'ambil']))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
