<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_state_is_tracked_per_admin(): void
    {
        $student = User::factory()->create();
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();

        Message::create([
            'user_id' => $student->id,
            'sender_id' => $student->id,
            'body' => 'Halo, saya mau bertanya soal peminjaman.',
        ]);

        $this->assertSame(1, Message::unreadCountFor($adminA));
        $this->assertSame(1, Message::unreadCountFor($adminB));

        // Admin A membuka percakapan: hanya badge admin A yang bersih.
        $this->actingAs($adminA)
            ->get(route('admin.chat.show', $student->id))
            ->assertOk();

        $this->assertSame(0, Message::unreadCountFor($adminA));
        $this->assertSame(1, Message::unreadCountFor($adminB));

        // Admin B membalas: siswa mendapat satu pesan belum dibaca.
        $this->actingAs($adminB)
            ->postJson(route('admin.chat.kirim', $student->id), [
                'body' => 'Halo, silakan jelaskan kendalanya.',
            ])
            ->assertCreated();

        $this->assertSame(1, Message::unreadCountFor($student->fresh()));

        // Siswa membuka chat: badge siswa bersih.
        $this->actingAs($student)
            ->get(route('chat.index'))
            ->assertOk();

        $this->assertSame(0, Message::unreadCountFor($student->fresh()));
    }

    public function test_student_cannot_open_admin_chat_routes(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.chat.index'))
            ->assertForbidden();
    }
}
