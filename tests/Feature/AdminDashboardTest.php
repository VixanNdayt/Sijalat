<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Message;
use App\Models\Pinjam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function buatAlat(string $nama = 'Router Dashboard'): Alat
    {
        return Alat::create([
            'code' => 'ALT-DASH-' . fake()->unique()->numerify('###'),
            'nama' => $nama,
            'jumlah_stok' => 5,
            'description' => 'Alat untuk pengujian dashboard',
            'jenis' => 'router',
            'kondisi_baik' => 5,
            'lokasi' => 'Lab Jaringan',
        ]);
    }

    private function buatPinjam(User $student, Alat $alat, array $tambahan = []): Pinjam
    {
        return Pinjam::create(array_merge([
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->addHour(),
            'dikembalikan_saat' => now()->addHours(2),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'pending',
        ], $tambahan));
    }

    public function test_dashboard_admin_menampilkan_guru_status_dan_tombol_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Siswa Dashboard']);
        $alat = $this->buatAlat('Access Point Dashboard');
        $guru = Guru::create(['nama' => 'Guru Dashboard']);

        $pinjam = $this->buatPinjam($student, $alat, [
            'guru_id' => $guru->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Guru')
            ->assertSee('Status')
            ->assertSee('Guru Dashboard')
            ->assertSee('Menunggu')
            ->assertSee('Access Point Dashboard')
            ->assertSee(route('admin.transaksi.detail', $pinjam->id), false)
            ->assertSee('notifBtn', false);
    }

    public function test_admin_bisa_membuka_detail_transaksi(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'name' => 'Siswa Detail',
            'nis' => '12345678',
            'kelas' => 'XI SIJA A',
        ]);
        $alat = $this->buatAlat('Switch Detail');
        $guru = Guru::create(['nama' => 'Guru Detail']);
        $mapel = Mapel::create(['nama_mapel' => 'Mapel Detail']);

        $pinjam = $this->buatPinjam($student, $alat, [
            'kode_pinjam' => 'SJL-DETAIL',
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'jumlah_minta' => 2,
            'deskripsi_minta' => 'Praktik konfigurasi switch',
            'ruangan' => 'Lab Switch',
            'status' => 'disetuji',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.transaksi.detail', $pinjam->id))
            ->assertOk()
            ->assertJsonPath('kode_pinjam', 'SJL-DETAIL')
            ->assertJsonPath('status_label', 'Disetujui')
            ->assertJsonPath('status_tone', 'disetujui')
            ->assertJsonPath('siswa.nama', 'Siswa Detail')
            ->assertJsonPath('siswa.nis', '12345678')
            ->assertJsonPath('alat.nama', 'Switch Detail')
            ->assertJsonPath('alat.guru', 'Guru Detail')
            ->assertJsonPath('alat.mapel', 'Mapel Detail')
            ->assertJsonPath('alat.jumlah', '2 unit')
            ->assertJsonPath('keterangan.keperluan', 'Praktik konfigurasi switch')
            ->assertJsonPath('keterangan.ruangan', 'Lab Switch')
            ->assertJsonPath('bukti.ambil', null)
            ->assertJsonPath('bukti.kembali', null)
            ->assertJsonStructure([
                'waktu' => ['diajukan', 'mulai_pinjam', 'tenggat_kembali', 'dikembalikan', 'kondisi_kembali'],
            ]);
    }

    public function test_siswa_tidak_boleh_membuka_detail_transaksi_admin(): void
    {
        $student = User::factory()->create();
        $alat = $this->buatAlat();
        $pinjam = $this->buatPinjam($student, $alat);

        $this->actingAs($student)
            ->getJson(route('admin.transaksi.detail', $pinjam->id))
            ->assertForbidden();
    }

    public function test_notifikasi_admin_berisi_pending_chat_dan_terlambat(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Siswa Notifikasi']);
        $alat = $this->buatAlat('Alat Notifikasi');

        $this->buatPinjam($student, $alat, ['status' => 'pending']);

        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => now()->subDays(3),
            'dikembalikan_saat' => now()->subDay(),
            'dikembalikan_aktual' => null,
            'status' => 'dipinjam',
        ]);

        Message::create([
            'user_id' => $student->id,
            'sender_id' => $student->id,
            'body' => 'Pak, boleh pinjam alat lagi?',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('sections.0.key', 'pending')
            ->assertJsonPath('sections.0.count', 1)
            ->assertJsonPath('sections.1.key', 'chat')
            ->assertJsonPath('sections.1.count', 1)
            ->assertJsonPath('sections.1.items.0.title', 'Siswa Notifikasi')
            ->assertJsonPath('sections.2.key', 'terlambat')
            ->assertJsonPath('sections.2.count', 1);
    }

    public function test_notifikasi_admin_menghitung_chat_yang_sudah_dibaca_sebagai_nol(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $pesan = Message::create([
            'user_id' => $student->id,
            'sender_id' => $student->id,
            'body' => 'Pesan lama.',
        ]);

        Message::markRead($admin, $student->id);

        $this->actingAs($admin)
            ->getJson(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('sections', []);

        $this->assertNotNull($pesan);
    }

    public function test_siswa_tidak_boleh_mengakses_notifikasi_admin(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->getJson(route('admin.notifikasi'))
            ->assertForbidden();
    }

    public function test_siswa_mendapat_notifikasi_pengambilan_pengembalian_dan_chat(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 08:00:00'));

        $admin = User::factory()->admin()->create(['name' => 'Admin Notif']);
        $student = User::factory()->create(['name' => 'Siswa Notif']);
        $alat = $this->buatAlat('Alat Notif Siswa');

        // Menunggu persetujuan.
        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 09:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'status' => 'pending',
        ]);

        // Sudah disetujui, siap diambil.
        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 09:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'status' => 'disetuji',
        ]);

        // Sedang dipinjam dan sudah melewati tenggat.
        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-06 09:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-06 15:00:00'),
            'status' => 'dipinjam',
        ]);

        Message::create([
            'user_id' => $student->id,
            'sender_id' => $admin->id,
            'body' => 'Alatnya sudah bisa diambil ya.',
        ]);

        $this->actingAs($student)
            ->getJson(route('notifikasi'))
            ->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonPath('sections.0.key', 'kembali')
            ->assertJsonPath('sections.1.key', 'ambil')
            ->assertJsonPath('sections.2.key', 'pending')
            ->assertJsonPath('sections.3.key', 'chat')
            ->assertJsonPath('sections.3.items.0.title', 'Admin Notif');
    }

    public function test_siswa_mendapat_notifikasi_saat_waktunya_mengambil_dan_mengembalikan(): void
    {
        // Senin, 9 November 2026, 45 menit sebelum jam terakhir pembelajaran.
        $this->travelTo(Carbon::parse('2026-11-09 15:45:00'));

        $student = User::factory()->create(['name' => 'Siswa Jadwal']);
        $alat = $this->buatAlat('Alat Jadwal');

        // Jadwal ambil hari ini pukul 06.30.
        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 06:30:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 16:30:00'),
            'status' => 'disetuji',
        ]);

        // Sedang dipinjam dan tenggatnya tinggal 45 menit lagi.
        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 16:30:00'),
            'status' => 'dipinjam',
        ]);

        $this->actingAs($student)
            ->getJson(route('notifikasi'))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('sections.0.key', 'kembali_segera')
            ->assertJsonPath('sections.0.count', 1)
            ->assertJsonPath('sections.1.key', 'ambil')
            ->assertJsonPath('sections.1.count', 1);
    }

    public function test_admin_mendapat_notifikasi_jadwal_pengambilan_dan_pengembalian_hari_ini(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 08:00:00'));

        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Siswa Jadwal Admin']);
        $alat = $this->buatAlat('Alat Jadwal Admin');

        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 09:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'status' => 'disetuji',
        ]);

        $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'status' => 'dipinjam',
        ]);

        $this->actingAs($admin)
            ->getJson(route('notifikasi'))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('sections.0.key', 'ambil_hari_ini')
            ->assertJsonPath('sections.0.count', 1)
            ->assertJsonPath('sections.1.key', 'kembali_hari_ini')
            ->assertJsonPath('sections.1.count', 1);
    }

    public function test_terlambat_label_menghitung_keterlambatan_pada_hari_yang_sama(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 17:00:00'));

        $student = User::factory()->create();
        $alat = $this->buatAlat('Alat Telat');

        $pinjam = $this->buatPinjam($student, $alat, [
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 16:30:00'),
            'status' => 'dipinjam',
        ]);

        $this->assertSame('Terlambat 30 menit', $pinjam->terlambat_label);
        $this->assertSame('dipinjam', $pinjam->status);
    }

    public function test_navbar_siswa_menampilkan_tombol_notifikasi(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('notifBtn', false)
            ->assertSee(route('notifikasi'), false);
    }
}
