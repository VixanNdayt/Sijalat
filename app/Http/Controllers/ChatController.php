<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Halaman chat untuk siswa.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.chat.index');
        }

        $messages = Message::with('sender')
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        Message::where('user_id', $user->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('chat.siswa', compact('messages'));
    }

    /**
     * Ambil pesan baru untuk polling siswa.
     */
    public function pesan(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            abort(403);
        }

        $after = max(0, (int) $request->query('after', 0));

        $messages = Message::with('sender')
            ->where('user_id', $user->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get();

        Message::where('user_id', $user->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (Message $message) => $this->payload($message, $user->id))->values(),
            'last_id' => (int) ($messages->last()->id ?? $after),
        ]);
    }

    /**
     * Kirim pesan dari siswa.
     */
    public function kirim(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            abort(403);
        }

        $request->merge(['body' => trim((string) $request->input('body'))]);

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'user_id' => $user->id,
            'sender_id' => $user->id,
            'body' => $data['body'],
        ]);

        $message->load('sender');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message, $user->id),
            ], 201);
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Daftar percakapan untuk admin.
     */
    public function adminIndex(Request $request)
    {
        $this->pastikanAdmin($request);

        return view('chat.admin', [
            'students' => $this->daftarSiswa(),
            'active' => null,
            'messages' => collect(),
        ]);
    }

    /**
     * Buka percakapan dengan satu siswa.
     */
    public function adminShow(Request $request, User $user)
    {
        $this->pastikanAdmin($request);

        if ($user->isAdmin()) {
            abort(404);
        }

        $messages = Message::with('sender')
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        Message::where('user_id', $user->id)
            ->where('sender_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('chat.admin', [
            'students' => $this->daftarSiswa(),
            'active' => $user,
            'messages' => $messages,
        ]);
    }

    /**
     * Ambil pesan baru untuk polling admin.
     */
    public function adminPesan(Request $request, User $user)
    {
        $this->pastikanAdmin($request);

        if ($user->isAdmin()) {
            abort(404);
        }

        $after = max(0, (int) $request->query('after', 0));

        $messages = Message::with('sender')
            ->where('user_id', $user->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get();

        Message::where('user_id', $user->id)
            ->where('sender_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (Message $message) => $this->payload($message, $request->user()->id))->values(),
            'last_id' => (int) ($messages->last()->id ?? $after),
        ]);
    }

    /**
     * Kirim balasan admin ke siswa.
     */
    public function adminKirim(Request $request, User $user)
    {
        $this->pastikanAdmin($request);

        if ($user->isAdmin()) {
            abort(404);
        }

        $request->merge(['body' => trim((string) $request->input('body'))]);

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'user_id' => $user->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $message->load('sender');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message, $request->user()->id),
            ], 201);
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Data siswa yang punya percakapan, diurutkan dari pesan terbaru.
     */
    private function daftarSiswa()
    {
        $students = User::query()
            ->where('role_user', '!=', 'admin')
            ->whereHas('messages')
            ->withCount(['messages as unread_count' => function ($query) {
                $query->whereColumn('messages.sender_id', 'users.id')
                    ->whereNull('messages.read_at');
            }])
            ->withMax('messages', 'id')
            ->orderByDesc('messages_max_id')
            ->get();

        $lastMessages = Message::whereIn('id', $students->pluck('messages_max_id')->filter())
            ->get()
            ->keyBy('user_id');

        $students->each(function (User $student) use ($lastMessages) {
            $student->last_message = $lastMessages->get($student->id);
        });

        return $students;
    }

    /**
     * Format satu pesan untuk tampilan dan polling.
     */
    private function payload(Message $message, int $viewerId): array
    {
        $waktu = Carbon::parse($message->created_at)->timezone('Asia/Jakarta');

        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name ?? 'Pengguna',
            'mine' => (int) $message->sender_id === $viewerId,
            'time' => $waktu->format('H.i'),
            'date' => $waktu->format('Y-m-d'),
            'date_label' => $waktu->locale('id')->translatedFormat('l, d F Y'),
        ];
    }

    private function pastikanAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->isAdmin(),
            403,
            'Halaman ini hanya dapat diakses admin.'
        );
    }
}
