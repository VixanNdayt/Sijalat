<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransaksiFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat file PNG 1x1 asli tanpa bergantung pada ekstensi GD.
     */
    private function fakeProofPhoto(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        );

        $path = tempnam(sys_get_temp_dir(), 'bukti-') . '.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, 'bukti.png', 'image/png', null, true);
    }

    public function test_pengembalian_tidak_bisa_diproses_dua_kali(): void
    {
        Storage::fake('local');

        // Senin, 9 November 2026 pukul 10.00 (sebelum jam terakhir pembelajaran).
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));

        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-TEST-1',
            'nama' => 'Router Test',
            'jumlah_stok' => 4,
            'description' => 'Router untuk pengujian',
            'jenis' => 'router',
            'kondisi_baik' => 4,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-TEST01',
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => 'token-kembali-tes',
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subHour(),
            'dikembalikan_saat' => now()->addHour(),
            'jumlah_minta' => 2,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 1',
            'status' => 'dipinjam',
        ]);

        $payload = [
            'aksi' => 'KEMBALI',
            'pinjam_id' => $pinjam->id,
            'qr_token' => 'token-kembali-tes',
            'nis' => $student->nis,
            'barang' => $alat->nama,
            'bukti_foto' => $this->fakeProofPhoto(),
            'kondisi_kembali' => 'baik',
        ];

        $this->actingAs($student)
            ->post(route('transaksi.proses'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertSame('dikembalikan', $pinjam->fresh()->status);
        $this->assertSame(6, $alat->fresh()->jumlah_stok);

        // Bukti foto harus tersimpan di disk privat, bukan disk publik.
        Storage::disk('local')->assertExists($pinjam->fresh()->bukti_kembali);

        // Kirim ulang permintaan yang sama (mis. tombol ditekan dua kali).
        $this->actingAs($student)
            ->post(route('transaksi.proses'), $payload)
            ->assertSessionHas('error');

        $this->assertSame('dikembalikan', $pinjam->fresh()->status);
        $this->assertSame(6, $alat->fresh()->jumlah_stok);
    }

    public function test_pengembalian_rusak_tidak_menambah_total_unit_fisik(): void
    {
        Storage::fake('local');

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));

        $student = User::factory()->create();

        // Data lama yang tidak konsisten: kondisi baik lebih kecil dari qty pinjam.
        $alat = Alat::create([
            'code' => 'ALT-TEST-3',
            'nama' => 'Router Rusak',
            'jumlah_stok' => 1,
            'description' => 'Router untuk pengujian kondisi rusak',
            'jenis' => 'router',
            'kondisi_baik' => 1,
            'kondisi_pengecekan' => 2,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-TEST03',
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => 'token-kembali-tes-3',
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subHour(),
            'dikembalikan_saat' => now()->addHour(),
            'jumlah_minta' => 2,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 3',
            'status' => 'dipinjam',
        ]);

        $this->actingAs($student)
            ->post(route('transaksi.proses'), [
                'aksi' => 'KEMBALI',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-kembali-tes-3',
                'nis' => $student->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
                'kondisi_kembali' => 'rusak',
            ])
            ->assertRedirect(route('dashboard'));

        $alat->refresh();

        $this->assertSame(0, (int) $alat->kondisi_baik);
        $this->assertSame(1, (int) $alat->kondisi_rusak);
        // Total unit fisik tetap 3, tidak berubah menjadi 4.
        $this->assertSame(3, $alat->totalFisik());
    }

    public function test_pengambilan_sebelum_pukul_0630_ditolak(): void
    {
        Storage::fake('local');

        $this->travelTo(Carbon::parse('2026-11-09 06:00:00'));

        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-JAM-1',
            'nama' => 'Router Pagi',
            'jumlah_stok' => 3,
            'description' => 'Router untuk pengujian jadwal',
            'jenis' => 'router',
            'kondisi_baik' => 3,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-JAM01',
            'qr_ambil_token' => 'token-ambil-pagi',
            'qr_kembali_token' => Str::random(48),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 1',
            'status' => 'disetuji',
        ]);

        $this->actingAs($student)
            ->post(route('transaksi.proses'), [
                'aksi' => 'AMBIL',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-ambil-pagi',
                'nis' => $student->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
            ])
            ->assertSessionHas('error');

        $this->assertSame('disetuji', $pinjam->fresh()->status);
        $this->assertSame(3, (int) $alat->fresh()->jumlah_stok);
    }

    public function test_pengambilan_pada_tanggal_yang_berbeda_ditolak(): void
    {
        Storage::fake('local');

        $this->travelTo(Carbon::parse('2026-11-10 07:00:00'));

        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-JAM-2',
            'nama' => 'Switch Jadwal',
            'jumlah_stok' => 2,
            'description' => 'Switch untuk pengujian jadwal',
            'jenis' => 'switch',
            'kondisi_baik' => 2,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-JAM02',
            'qr_ambil_token' => 'token-ambil-beda-hari',
            'qr_kembali_token' => Str::random(48),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 2',
            'status' => 'disetuji',
        ]);

        $this->actingAs($student)
            ->post(route('transaksi.proses'), [
                'aksi' => 'AMBIL',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-ambil-beda-hari',
                'nis' => $student->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
            ])
            ->assertSessionHas('error');

        $this->assertSame('disetuji', $pinjam->fresh()->status);
    }

    public function test_pengambilan_tepat_pukul_0630_diterima(): void
    {
        Storage::fake('local');

        $this->travelTo(Carbon::parse('2026-11-09 06:30:00'));

        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-JAM-3',
            'nama' => 'Access Point Pagi',
            'jumlah_stok' => 2,
            'description' => 'Access point untuk pengujian jadwal',
            'jenis' => 'router',
            'kondisi_baik' => 2,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-JAM03',
            'qr_ambil_token' => 'token-ambil-0630',
            'qr_kembali_token' => Str::random(48),
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => Carbon::parse('2026-11-09 06:30:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 15:00:00'),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 3',
            'status' => 'disetuji',
        ]);

        $this->actingAs($student)
            ->post(route('transaksi.proses'), [
                'aksi' => 'AMBIL',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-ambil-0630',
                'nis' => $student->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('dipinjam', $pinjam->fresh()->status);
        $this->assertSame(1, (int) $alat->fresh()->jumlah_stok);
    }

    public function test_pengembalian_setelah_jam_terakhir_pembelajaran_ditolak(): void
    {
        Storage::fake('local');

        // Senin, jam terakhir 16.30. Pukul 16.31 sudah lewat.
        $this->travelTo(Carbon::parse('2026-11-09 16:31:00'));

        $student = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-JAM-4',
            'nama' => 'Router Sore',
            'jumlah_stok' => 1,
            'description' => 'Router untuk pengujian jadwal',
            'jenis' => 'router',
            'kondisi_baik' => 1,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-JAM04',
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => 'token-kembali-sore',
            'user_id' => $student->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => Carbon::parse('2026-11-09 07:00:00'),
            'dikembalikan_saat' => Carbon::parse('2026-11-09 16:30:00'),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 4',
            'status' => 'dipinjam',
        ]);

        $this->actingAs($student)
            ->post(route('transaksi.proses'), [
                'aksi' => 'KEMBALI',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-kembali-sore',
                'nis' => $student->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
                'kondisi_kembali' => 'baik',
            ])
            ->assertSessionHas('error');

        $this->assertSame('dipinjam', $pinjam->fresh()->status);
    }

    public function test_siswa_tidak_bisa_memproses_peminjaman_milik_orang_lain(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $other = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-TEST-2',
            'nama' => 'Switch Test',
            'jumlah_stok' => 2,
            'description' => 'Switch untuk pengujian',
            'jenis' => 'switch',
            'kondisi_baik' => 2,
        ]);

        $pinjam = Pinjam::create([
            'kode_pinjam' => 'SJL-TEST02',
            'qr_ambil_token' => 'token-ambil-tes',
            'qr_kembali_token' => 'token-kembali-tes-2',
            'user_id' => $owner->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now(),
            'dikembalikan_saat' => now()->addHour(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Keperluan praktik',
            'ruangan' => 'Lab 2',
            'status' => 'disetuji',
        ]);

        $this->actingAs($other)
            ->post(route('transaksi.proses'), [
                'aksi' => 'AMBIL',
                'pinjam_id' => $pinjam->id,
                'qr_token' => 'token-ambil-tes',
                'nis' => $other->nis,
                'barang' => $alat->nama,
                'bukti_foto' => $this->fakeProofPhoto(),
            ])
            ->assertSessionHas('error');

        $this->assertSame('disetuji', $pinjam->fresh()->status);
        $this->assertSame(2, $alat->fresh()->jumlah_stok);
    }
}
