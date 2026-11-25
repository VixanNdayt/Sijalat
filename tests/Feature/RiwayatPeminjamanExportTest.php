<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Pinjam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatPeminjamanExportTest extends TestCase
{
    use RefreshDatabase;

    private function buatAlat(string $nama = 'Router Riwayat'): Alat
    {
        return Alat::create([
            'code' => 'ALT-RWT-' . fake()->unique()->numerify('###'),
            'nama' => $nama,
            'jumlah_stok' => 5,
            'description' => 'Alat untuk pengujian riwayat',
            'jenis' => 'router',
            'kondisi_baik' => 5,
            'lokasi' => 'Lab Jaringan',
        ]);
    }

    private function buatPinjam(User $student, Alat $alat, array $tambahan = []): Pinjam
    {
        return Pinjam::create(array_merge([
            'kode_pinjam' => 'SJL-RWT-' . fake()->unique()->numerify('####'),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->startOfDay()->addHours(8),
            'dikembalikan_saat' => now()->startOfDay()->addHours(15),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'dikembalikan',
        ], $tambahan));
    }

    public function test_admin_melihat_riwayat_hanya_pada_rentang_tanggal(): void
    {
        $admin = User::factory()->admin()->create();
        $dalam = User::factory()->create(['name' => 'Siswa Dalam Rentang']);
        $luar = User::factory()->create(['name' => 'Siswa Luar Rentang']);
        $alat = $this->buatAlat();

        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $this->buatPinjam($dalam, $alat);

        $this->travelTo(Carbon::parse('2026-12-10 09:00:00'));
        $this->buatPinjam($luar, $alat);

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

        $this->actingAs($admin)
            ->get(route('admin.riwayat', ['mulai' => '2026-11-01', 'selesai' => '2026-11-30']))
            ->assertOk()
            ->assertSee('Siswa Dalam Rentang')
            ->assertDontSee('Siswa Luar Rentang')
            ->assertSee('Riwayat Peminjaman')
            ->assertSee(route('admin.riwayat.pdf'), false)
            ->assertSee(route('admin.riwayat.excel'), false)
            ->assertSee('id="themeToggle"', false)
            ->assertSee('css/theme-dark.css', false)
            ->assertSee('js/theme.js', false);
    }

    public function test_export_pdf_tanpa_gambar_bukti(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Siswa PDF Riwayat']);
        $alat = $this->buatAlat('Switch PDF Riwayat');
        $guru = Guru::create(['nama' => 'Guru PDF Riwayat']);
        $mapel = Mapel::create(['nama_mapel' => 'Mapel PDF Riwayat']);

        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));

        $this->buatPinjam($student, $alat, [
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'bukti_ambil' => 'private/bukti/rahasia-ambil.jpg',
            'bukti_kembali' => 'private/bukti/rahasia-kembali.jpg',
        ]);

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

        $response = $this->actingAs($admin)->get(route('admin.riwayat.pdf', [
            'mulai' => '2026-11-01',
            'selesai' => '2026-11-30',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $content = $response->getContent();

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertStringContainsString('Siswa PDF Riwayat', $content);
        $this->assertStringContainsString('Guru PDF Riwayat', $content);
        $this->assertStringNotContainsString('rahasia-ambil.jpg', $content);
        $this->assertStringNotContainsString('rahasia-kembali.jpg', $content);
        $this->assertStringNotContainsString('bukti_ambil', $content);
        $this->assertStringNotContainsString('bukti_kembali', $content);
    }

    public function test_export_excel_hanya_memuat_data_dalam_rentang_tanpa_gambar(): void
    {
        $admin = User::factory()->admin()->create();
        $dalam = User::factory()->create(['name' => 'Siswa Excel Dalam']);
        $luar = User::factory()->create(['name' => 'Siswa Excel Luar']);
        $alat = $this->buatAlat('Laptop Excel Riwayat');

        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $this->buatPinjam($dalam, $alat, [
            'bukti_ambil' => 'private/bukti/rahasia-ambil.jpg',
        ]);

        $this->travelTo(Carbon::parse('2026-12-10 09:00:00'));
        $this->buatPinjam($luar, $alat);

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

        $response = $this->actingAs($admin)->get(route('admin.riwayat.excel', [
            'mulai' => '2026-11-01',
            'selesai' => '2026-11-30',
        ]));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $content = $response->getContent();

        $this->assertStringContainsString('Siswa Excel Dalam', $content);
        $this->assertStringNotContainsString('Siswa Excel Luar', $content);
        $this->assertStringNotContainsString('rahasia-ambil.jpg', $content);
        $this->assertStringNotContainsString('bukti_ambil', $content);
        $this->assertStringNotContainsString('bukti_kembali', $content);
    }

    public function test_siswa_tidak_boleh_mengakses_riwayat_dan_ekspornya(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.riwayat'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.riwayat.pdf'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.riwayat.excel'))->assertForbidden();
    }

    public function test_tanggal_terbalik_dinormalkan_menjadi_rentang_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Siswa Tanggal Terbalik']);
        $alat = $this->buatAlat();

        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $this->buatPinjam($student, $alat);

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

        $this->actingAs($admin)
            ->get(route('admin.riwayat', ['mulai' => '2026-11-30', 'selesai' => '2026-11-01']))
            ->assertOk()
            ->assertSee('Siswa Tanggal Terbalik');
    }
}
