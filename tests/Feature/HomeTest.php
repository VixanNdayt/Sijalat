<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private function buatAlat(array $attributes = []): Alat
    {
        return Alat::create(array_merge([
            'code' => 'ALT-HOME-1',
            'lokasi' => 'Lab Jaringan - Rak A1',
            'nama' => 'Switch Cisco 24 Port',
            'jumlah_stok' => 6,
            'description' => 'Switch manageable untuk praktik konfigurasi VLAN.',
            'jenis' => 'switch',
            'kondisi_baik' => 6,
            'kondisi_rusak' => 1,
            'kondisi_maintenance' => 1,
        ], $attributes));
    }

    public function test_guest_melihat_hero_statistik_dan_cta(): void
    {
        $this->buatAlat();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sistem Informasi Peminjaman Alat');
        $response->assertSee('Jelajahi Katalog Alat');
        $response->assertSee('Cek Status Pinjaman Saya');
        $response->assertSee('Tersedia');
        $response->assertSee('Sedang dipinjam');
        $response->assertSee('Perawatan');
        $response->assertSee('Switch Cisco 24 Port');
        $response->assertSee('Lab Jaringan - Rak A1');
        $response->assertSee('Masuk untuk melihat aktivitasmu');
        $response->assertSee('Pindai');
    }

    public function test_pencarian_alat_berdasarkan_nama_dan_kode(): void
    {
        $switch = $this->buatAlat();

        $router = Alat::create([
            'code' => 'ALT-HOME-2',
            'lokasi' => 'Lab Komputer - Rak B2',
            'nama' => 'Router Mikrotik',
            'jumlah_stok' => 3,
            'description' => 'Router untuk praktik routing.',
            'jenis' => 'router',
            'kondisi_baik' => 3,
        ]);

        $this->get('/?q=cisco')
            ->assertOk()
            ->assertSee('data-alat-card="' . $switch->id . '"', false)
            ->assertDontSee('data-alat-card="' . $router->id . '"', false);

        $this->get('/?q=ALT-HOME-2')
            ->assertOk()
            ->assertSee('data-alat-card="' . $router->id . '"', false)
            ->assertDontSee('data-alat-card="' . $switch->id . '"', false);
    }

    public function test_filter_kategori_hanya_menampilkan_jenis_terkait(): void
    {
        $switch = $this->buatAlat();

        $laptop = Alat::create([
            'code' => 'ALT-HOME-3',
            'lokasi' => 'Lab Komputer',
            'nama' => 'Laptop Praktik ASUS',
            'jumlah_stok' => 4,
            'description' => 'Laptop untuk praktik.',
            'jenis' => 'laptop',
            'kondisi_baik' => 4,
        ]);

        $this->get('/?jenis=laptop')
            ->assertOk()
            ->assertSee('data-alat-card="' . $laptop->id . '"', false)
            ->assertDontSee('data-alat-card="' . $switch->id . '"', false);
    }

    public function test_pencarian_tanpa_hasil_menampilkan_kondisi_kosong(): void
    {
        $this->buatAlat();

        $this->get('/?q=tidak-ada-alat-ini')
            ->assertOk()
            ->assertSee('Alat tidak ditemukan')
            ->assertSee('Tampilkan semua alat');
    }

    public function test_siswa_login_melihat_widget_pinjaman_aktif(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        Pinjam::create([
            'kode_pinjam' => 'PJM-HOME-1',
            'user_id' => $siswa->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->subHour(),
            'dikembalikan_saat' => now()->addDay(),
            'ruangan' => 'Lab Jaringan',
            'jumlah_minta' => 2,
            'deskripsi_minta' => 'Praktik VLAN',
            'status' => 'dipinjam',
        ]);

        $this->actingAs($siswa)
            ->get('/')
            ->assertOk()
            ->assertSee('Switch Cisco 24 Port')
            ->assertSee('Perpanjang')
            ->assertSee('Lapor kendala')
            ->assertDontSee('Masuk untuk melihat aktivitasmu');
    }

    public function test_admin_melihat_panel_cepat_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->buatAlat();

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Panel cepat admin')
            ->assertSee('Buka approval');
    }

    public function test_katalog_beranda_hanya_menampilkan_beberapa_alat_contoh(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Alat::create([
                'code' => 'ALT-CONTOH-' . $i,
                'lokasi' => 'Lab Jaringan',
                'nama' => 'Alat Contoh ' . $i,
                'jumlah_stok' => 2,
                'description' => 'Alat contoh nomor ' . $i,
                'jenis' => 'switch',
                'kondisi_baik' => 2,
            ]);
        }

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Tampilkan semua 8 alat');
        $response->assertSee('home-carousel-controls');
        $response->assertSee('id="alatTrack"', false);
        $this->assertSame(6, substr_count($response->getContent(), 'data-alat-card='));

        $semua = $this->get('/?semua=1');

        $semua->assertOk();
        $semua->assertDontSee('Tampilkan semua 8 alat');
        $semua->assertDontSee('home-carousel-controls');
        $this->assertSame(8, substr_count($semua->getContent(), 'data-alat-card='));
    }

    public function test_keranjang_dari_homepage_diteruskan_ke_form_peminjaman(): void
    {
        $siswa = User::factory()->create();
        $alat = $this->buatAlat();

        $this->actingAs($siswa)
            ->get(route('pinjam.create', ['cart' => $alat->id . ':2']))
            ->assertOk()
            ->assertSee('"qty":2', false);
    }
}
