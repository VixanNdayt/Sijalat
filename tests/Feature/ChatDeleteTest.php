<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatDeleteTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function gambarPng(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'chat').'.png';
        file_put_contents($path, base64_decode(self::PNG_1PX));

        return new UploadedFile($path, 'foto.png', 'image/png', null, true);
    }

    public function test_siswa_bisa_menghapus_pesannya_sendiri_beserta_gambarnya(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->post(
            route('chat.kirim'),
            ['body' => 'Pesan akan dihapus.', 'image' => $this->gambarPng()],
            ['Accept' => 'application/json']
        )->assertCreated();

        $pesan = Message::firstOrFail();
        $path = $pesan->image_path;

        Storage::disk('local')->assertExists($path);

        $this->actingAs($siswa)
            ->deleteJson(route('chat.destroy', $pesan->id))
            ->assertOk()
            ->assertJsonPath('deleted_id', $pesan->id);

        $this->assertSame(0, Message::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_siswa_tidak_boleh_menghapus_pesan_admin(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.chat.kirim', $siswa->id), [
            'body' => 'Balasan admin.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($siswa)
            ->deleteJson(route('chat.destroy', $pesan->id))
            ->assertForbidden();

        $this->assertSame(1, Message::count());
    }

    public function test_siswa_tidak_boleh_menghapus_pesan_siswa_lain(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($pemilik)->postJson(route('chat.kirim'), [
            'body' => 'Pesan milik siswa A.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($lain)
            ->deleteJson(route('chat.destroy', $pesan->id))
            ->assertForbidden();

        $this->assertSame(1, Message::count());
    }

    public function test_admin_bisa_menghapus_pesan_siswa(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), [
            'body' => 'Pesan tidak pantas.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($admin)
            ->deleteJson(route('chat.destroy', $pesan->id))
            ->assertOk();

        $this->assertSame(0, Message::count());
    }

    public function test_siswa_bisa_menghapus_seluruh_riwayatnya(): void
    {
        $siswa = User::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($siswa)->post(
            route('chat.kirim'),
            ['body' => 'Satu.', 'image' => $this->gambarPng()],
            ['Accept' => 'application/json']
        )->assertCreated();

        $path = Message::firstOrFail()->image_path;

        $this->actingAs($siswa)->postJson(route('chat.kirim'), ['body' => 'Dua.'])->assertCreated();
        $this->actingAs($lain)->postJson(route('chat.kirim'), ['body' => 'Pesan siswa lain.'])->assertCreated();

        $this->actingAs($siswa)
            ->deleteJson(route('chat.clear'))
            ->assertOk()
            ->assertJsonPath('cleared', true);

        $this->assertSame(0, Message::where('user_id', $siswa->id)->count());
        $this->assertSame(1, Message::where('user_id', $lain->id)->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_admin_bisa_menghapus_riwayat_satu_siswa(): void
    {
        $siswa = User::factory()->create();
        $lain = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), ['body' => 'Halo admin.'])->assertCreated();
        $this->actingAs($lain)->postJson(route('chat.kirim'), ['body' => 'Halo juga.'])->assertCreated();

        $this->actingAs($admin)
            ->deleteJson(route('admin.chat.clear', $siswa->id))
            ->assertOk();

        $this->assertSame(0, Message::where('user_id', $siswa->id)->count());
        $this->assertSame(1, Message::where('user_id', $lain->id)->count());
    }

    public function test_siswa_tidak_boleh_memakai_aksi_hapus_admin(): void
    {
        $siswa = User::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($siswa)
            ->deleteJson(route('admin.chat.clear', $lain->id))
            ->assertForbidden();
    }

    public function test_pesan_yang_sudah_dihapus_menghasilkan_404(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), ['body' => 'Sementara.'])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($siswa)->deleteJson(route('chat.destroy', $pesan->id))->assertOk();
        $this->actingAs($siswa)->deleteJson(route('chat.destroy', $pesan->id))->assertNotFound();
    }
}
