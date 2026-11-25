<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Guru;
use App\Models\Mapel;
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
        $guru = Guru::create(['nama' => 'Guru Pengampu']);
        $mapel = Mapel::create(['nama_mapel' => 'Informatika']);

        // 9 November 2026 adalah hari Senin, dalam jam sekolah.
        return [
            'alat_id' => [$alat->id],
            'jumlah_minta' => [2],
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
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
        $this->assertDatabaseHas('pinjams', [
            'user_id' => $siswa->id,
            'guru_id' => Guru::first()->id,
            'mapel_id' => Mapel::first()->id,
        ]);
    }

    public function test_pengambilan_sebelum_pukul_0630_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = '2026-11-09 06:00:00';
        $payload['dikembalikan_saat'] = '2026-11-09 10:00:00';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dipinjam_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_pengambilan_tepat_pukul_0630_diterima(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = '2026-11-09 06:30:00';
        $payload['dikembalikan_saat'] = '2026-11-09 07:00:00';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('pinjams', 1);
    }

    public function test_pengembalian_setelah_jam_terakhir_pembelajaran_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = '2026-11-09 09:00:00';
        $payload['dikembalikan_saat'] = '2026-11-09 16:31:00';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dikembalikan_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_pengembalian_jumat_setelah_pukul_1445_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        // 13 November 2026 adalah hari Jumat.
        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = '2026-11-13 09:00:00';
        $payload['dikembalikan_saat'] = '2026-11-13 15:00:00';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dikembalikan_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_pengambilan_pada_hari_sabtu_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        // 14 November 2026 adalah hari Sabtu.
        $payload = $this->payload($alat);
        $payload['dipinjam_saat'] = '2026-11-14 09:00:00';
        $payload['dikembalikan_saat'] = '2026-11-14 10:00:00';

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors('dipinjam_saat');

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_pengajuan_tanpa_guru_dan_mapel_ditolak(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        unset($payload['guru_id'], $payload['mapel_id']);

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors(['guru_id', 'mapel_id']);

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_guru_dan_mapel_berbentuk_array_ditolak_tanpa_error_server(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['guru_id'] = [$payload['guru_id']];
        $payload['mapel_id'] = [$payload['mapel_id']];

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors(['guru_id', 'mapel_id']);

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_alat_yang_sama_tidak_bisa_dipilih_dua_kali(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $payload = $this->payload($alat);
        $payload['alat_id'] = [$alat->id, $alat->id];
        $payload['jumlah_minta'] = [1, 1];

        $this->actingAs($siswa)
            ->post(route('pinjam.store'), $payload)
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('pinjams', 0);
    }

    public function test_form_peminjaman_menampilkan_pilihan_guru_dan_mapel(): void
    {
        $siswa = User::factory()->create();
        $guru = Guru::create(['nama' => 'Guru Tampil']);
        $mapel = Mapel::create(['nama_mapel' => 'Mapel Tampil']);

        $this->actingAs($siswa)
            ->get(route('pinjam.create'))
            ->assertOk()
            ->assertSee($guru->nama)
            ->assertSee($mapel->nama_mapel)
            ->assertSee('data-custom-select', false)
            ->assertSee('js/custom-select.js', false);
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

        // Nama alat tidak boleh memutus atribut; konfirmasi kini lewat data-confirm (bukan onsubmit).
        $response->assertDontSee('onsubmit', false);
        $response->assertDontSee("peminjaman Kabel ');alert", false);
        $response->assertSee('data-confirm="Hapus pengajuan peminjaman Kabel &#039;);alert', false);
    }
}
