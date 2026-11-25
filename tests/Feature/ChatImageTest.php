<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatImageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function gambarPng(string $nama = 'foto.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'chat').'.png';
        file_put_contents($path, base64_decode(self::PNG_1PX));

        return new UploadedFile($path, $nama, 'image/png', null, true);
    }

    public function test_siswa_bisa_mengirim_gambar_saja(): void
    {
        $siswa = User::factory()->create();

        $response = $this->actingAs($siswa)->post(
            route('chat.kirim'),
            ['image' => $this->gambarPng()],
            ['Accept' => 'application/json']
        );

        $response->assertCreated();
        $response->assertJsonPath('message.image_url', fn ($url) => is_string($url) && $url !== '');
        $response->assertJsonPath('message.body', '');

        $pesan = Message::firstOrFail();

        $this->assertSame($siswa->id, $pesan->user_id);
        $this->assertSame($siswa->id, $pesan->sender_id);
        $this->assertNotNull($pesan->image_path);
        $this->assertStringStartsWith('chat/', $pesan->image_path);
        $this->assertSame('image/png', $pesan->image_mime);
        $this->assertGreaterThan(0, $pesan->image_size);

        Storage::disk('local')->assertExists($pesan->image_path);

        $this->actingAs($siswa)
            ->get(route('chat.image', $pesan->id))
            ->assertOk();
    }

    public function test_gambar_hanya_bisa_dilihat_pemilik_dan_admin(): void
    {
        $siswa = User::factory()->create();
        $lain = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($siswa)->post(
            route('chat.kirim'),
            ['body' => 'Ini foto kerusakan alat.', 'image' => $this->gambarPng()],
            ['Accept' => 'application/json']
        )->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($lain)
            ->get(route('chat.image', $pesan->id))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('chat.image', $pesan->id))
            ->assertOk();

        $this->actingAs($siswa)
            ->get(route('chat.image', $pesan->id))
            ->assertOk();
    }

    public function test_pesan_teks_tanpa_gambar_tetap_bisa(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), [
            'body' => 'Halo admin, saya ingin bertanya.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->assertSame('Halo admin, saya ingin bertanya.', $pesan->body);
        $this->assertNull($pesan->image_path);
    }

    public function test_pesan_tanpa_teks_dan_tanpa_gambar_ditolak(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), [
            'body' => '   ',
        ])->assertStatus(422)->assertJsonValidationErrors('body');

        $this->assertSame(0, Message::count());
    }

    public function test_file_yang_bukan_gambar_ditolak(): void
    {
        $siswa = User::factory()->create();

        $path = tempnam(sys_get_temp_dir(), 'chat').'.png';
        file_put_contents($path, 'ini bukan gambar');

        $file = new UploadedFile($path, 'palsu.png', 'image/png', null, true);

        $this->actingAs($siswa)->post(
            route('chat.kirim'),
            ['image' => $file],
            ['Accept' => 'application/json']
        )->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertSame(0, Message::count());
    }

    public function test_admin_bisa_membalas_dengan_gambar(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(
            route('admin.chat.kirim', $siswa->id),
            ['body' => 'Foto alat pengganti.', 'image' => $this->gambarPng('balasan.png')],
            ['Accept' => 'application/json']
        )->assertCreated();

        $pesan = Message::firstOrFail();

        $this->assertSame($siswa->id, $pesan->user_id);
        $this->assertSame($admin->id, $pesan->sender_id);
        $this->assertNotNull($pesan->image_path);
    }
}
