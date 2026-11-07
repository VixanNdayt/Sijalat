<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
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
        Storage::fake('public');

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

        // Kirim ulang permintaan yang sama (mis. tombol ditekan dua kali).
        $this->actingAs($student)
            ->post(route('transaksi.proses'), $payload)
            ->assertSessionHas('error');

        $this->assertSame('dikembalikan', $pinjam->fresh()->status);
        $this->assertSame(6, $alat->fresh()->jumlah_stok);
    }

    public function test_siswa_tidak_bisa_memproses_peminjaman_milik_orang_lain(): void
    {
        Storage::fake('public');

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
