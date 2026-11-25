<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_bisa_mengedit_pesannya_sendiri(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), [
            'body' => 'Teks awal.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($siswa)
            ->patchJson(route('chat.update', $pesan->id), ['body' => 'Teks sudah diedit.'])
            ->assertOk()
            ->assertJsonPath('message.body', 'Teks sudah diedit.')
            ->assertJsonPath('message.edited', true)
            ->assertJsonPath('message.can_edit', true);

        $pesan->refresh();

        $this->assertSame('Teks sudah diedit.', $pesan->body);
        $this->assertNotNull($pesan->edited_at);
    }

    public function test_siswa_tidak_boleh_mengedit_pesan_admin(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.chat.kirim', $siswa->id), [
            'body' => 'Balasan admin.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($siswa)
            ->patchJson(route('chat.update', $pesan->id), ['body' => 'Diubah siswa.'])
            ->assertForbidden();

        $this->assertSame('Balasan admin.', $pesan->fresh()->body);
    }

    public function test_siswa_tidak_boleh_mengedit_pesan_siswa_lain(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($pemilik)->postJson(route('chat.kirim'), [
            'body' => 'Pesan siswa A.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($lain)
            ->patchJson(route('chat.update', $pesan->id), ['body' => 'Dibajak.'])
            ->assertForbidden();

        $this->assertSame('Pesan siswa A.', $pesan->fresh()->body);
    }

    public function test_pesan_kosong_saat_edit_ditolak(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), ['body' => 'Awal.'])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($siswa)
            ->patchJson(route('chat.update', $pesan->id), ['body' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');

        $this->assertSame('Awal.', $pesan->fresh()->body);
    }

    public function test_admin_bisa_mengedit_pesannya_sendiri(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.chat.kirim', $siswa->id), [
            'body' => 'Balasan awal.',
        ])->assertCreated();

        $pesan = Message::firstOrFail();

        $this->actingAs($admin)
            ->patchJson(route('chat.update', $pesan->id), ['body' => 'Balasan revisi.'])
            ->assertOk();

        $this->assertSame('Balasan revisi.', $pesan->fresh()->body);
    }

    public function test_polling_mengirim_flag_hak_akses(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->postJson(route('chat.kirim'), ['body' => 'Pesan siswa.'])->assertCreated();

        $response = $this->actingAs($siswa)->getJson(route('chat.messages'));

        $response->assertOk();
        $response->assertJsonPath('messages.0.can_edit', true);
        $response->assertJsonPath('messages.0.can_delete', true);
        $response->assertJsonPath('messages.0.edited', false);
    }
}
