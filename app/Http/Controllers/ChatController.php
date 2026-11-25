<?php

namespace App\Http\Controllers;

use App\Models\ConversationRead;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        Message::markRead($user, $user->id);

        $topik = $request->query('topik', '');
        $topik = is_string($topik) ? $topik : '';
        $prefill = match ($topik) {
            'perpanjangan' => 'Halo admin, saya ingin mengajukan perpanjangan durasi peminjaman alat. Berikut detail pinjaman saya: ',
            'kerusakan' => 'Halo admin, saya ingin melaporkan kendala/kerusakan pada alat yang saya pinjam. Detail kendala: ',
            default => '',
        };

        return view('chat.siswa', compact('messages', 'prefill'));
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

        Message::markRead($user, $user->id);

        return response()->json([
            'messages' => $messages->map(fn (Message $message) => $this->payload($message, $user->id, false))->values(),
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

        $message = $this->simpanPesan($request, $user, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message, $user->id, false),
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
            'students' => $this->daftarSiswa($request->user()),
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

        Message::markRead($request->user(), $user->id);

        return view('chat.admin', [
            'students' => $this->daftarSiswa($request->user()),
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

        Message::markRead($request->user(), $user->id);

        return response()->json([
            'messages' => $messages->map(fn (Message $message) => $this->payload($message, $request->user()->id, true))->values(),
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

        $admin = $request->user();
        $message = $this->simpanPesan($request, $user, $admin);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message, $admin->id, true),
            ], 201);
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Sajikan gambar lampiran chat hanya untuk pemilik percakapan atau admin.
     */
    public function gambar(Request $request, Message $message)
    {
        $user = $request->user();

        $boleh = $user->isAdmin() || (int) $message->user_id === (int) $user->id;

        abort_unless($boleh, 403);

        $path = $message->image_path;

        $aman = is_string($path)
            && $path !== ''
            && str_starts_with($path, 'chat/')
            && ! str_contains($path, '..');

        abort_unless($aman, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Ubah teks pesan. Hanya pengirim pesan yang boleh mengedit.
     */
    public function ubahPesan(Request $request, Message $message)
    {
        $user = $request->user();

        abort_unless((int) $message->sender_id === (int) $user->id, 403);

        $body = $request->input('body');
        $request->merge(['body' => is_string($body) ? trim($body) : '']);

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ], [
            'body.required' => 'Pesan tidak boleh kosong.',
            'body.max' => 'Pesan terlalu panjang. Maksimal 2000 karakter.',
        ]);

        $message->body = $data['body'];
        $message->edited_at = now();
        $message->save();

        $message->load('sender');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->payload($message, $user->id, $user->isAdmin()),
            ]);
        }

        return back()->with('success', 'Pesan diperbarui.');
    }

    /**
     * Hapus satu pesan.
     *
     * Siswa hanya boleh menghapus pesannya sendiri; admin boleh menghapus
     * pesan apa pun dalam percakapan yang sedang dibuka.
     */
    public function hapusPesan(Request $request, Message $message)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $milikSendiri = (int) $message->sender_id === (int) $user->id
                && (int) $message->user_id === (int) $user->id;

            abort_unless($milikSendiri, 403);
        }

        $id = $message->id;

        $this->hapusFileGambar($message);
        $message->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted_id' => $id]);
        }

        return back()->with('success', 'Pesan dihapus.');
    }

    /**
     * Hapus seluruh riwayat chat siswa yang sedang login.
     */
    public function hapusPercakapan(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            abort(403);
        }

        $this->bersihkanPercakapan($user);

        if ($request->expectsJson()) {
            return response()->json(['cleared' => true]);
        }

        return back()->with('success', 'Riwayat chat dihapus.');
    }

    /**
     * Hapus seluruh riwayat chat satu siswa (aksi admin).
     */
    public function adminHapusPercakapan(Request $request, User $user)
    {
        $this->pastikanAdmin($request);

        if ($user->isAdmin()) {
            abort(404);
        }

        $this->bersihkanPercakapan($user);

        if ($request->expectsJson()) {
            return response()->json(['cleared' => true]);
        }

        return back()->with('success', 'Riwayat chat siswa dihapus.');
    }

    /**
     * Hapus semua pesan beserta gambar milik satu percakapan.
     */
    private function bersihkanPercakapan(User $pemilik): void
    {
        Message::where('user_id', $pemilik->id)
            ->get()
            ->each(fn (Message $message) => $this->hapusFileGambar($message));

        Message::where('user_id', $pemilik->id)->delete();

        ConversationRead::where('user_id', $pemilik->id)->delete();
    }

    /**
     * Hapus berkas gambar lampiran dari disk privat (jika ada).
     */
    private function hapusFileGambar(Message $message): void
    {
        $path = $message->image_path;

        if (! is_string($path) || $path === '' || ! str_starts_with($path, 'chat/')) {
            return;
        }

        if (str_contains($path, '..')) {
            return;
        }

        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Validasi lalu simpan satu pesan (teks, gambar, atau keduanya).
     */
    private function simpanPesan(Request $request, User $percakapan, User $pengirim): Message
    {
        $body = $request->input('body');
        $request->merge(['body' => is_string($body) ? trim($body) : '']);

        $data = $request->validate([
            'body' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:5120',
        ], [
            'body.max' => 'Pesan terlalu panjang. Maksimal 2000 karakter.',
            'image.image' => 'Lampiran harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
            'image.uploaded' => 'Gambar gagal diunggah karena melebihi batas server.',
        ]);

        $body = $data['body'] ?? '';
        $file = $request->file('image');

        if ($body === '' && ! $file) {
            throw ValidationException::withMessages([
                'body' => 'Tulis pesan atau lampirkan gambar terlebih dahulu.',
            ]);
        }

        $atribut = [
            'user_id' => $percakapan->id,
            'sender_id' => $pengirim->id,
            'body' => $body,
        ];

        if ($file) {
            $atribut['image_path'] = $file->store('chat', 'local');
            $atribut['image_mime'] = $file->getMimeType();
            $atribut['image_size'] = (int) $file->getSize();
        }

        $message = Message::create($atribut);
        $message->load('sender');

        return $message;
    }

    /**
     * Data siswa yang punya percakapan, diurutkan dari pesan terbaru.
     */
    private function daftarSiswa(User $reader)
    {
        $students = User::query()
            ->where('role_user', '!=', 'admin')
            ->whereHas('messages')
            ->withCount(['messages as unread_count' => function ($query) use ($reader) {
                $query->whereColumn('messages.sender_id', 'users.id')
                    ->whereRaw(
                        'messages.id > COALESCE(('
                        . 'SELECT last_read_message_id FROM conversation_reads '
                        . 'WHERE conversation_reads.user_id = messages.user_id '
                        . 'AND conversation_reads.reader_id = ?'
                        . '), 0)',
                        [$reader->id]
                    );
            }])
            ->withMax('messages', 'id')
            ->orderByDesc('messages_max_id')
            ->get();

        $lastMessages = Message::with('sender')
            ->whereIn('id', $students->pluck('messages_max_id')->filter())
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
    private function payload(Message $message, int $viewerId, bool $viewerIsAdmin = false): array
    {
        $waktu = Carbon::parse($message->created_at)->timezone('Asia/Jakarta');
        $pengirim = (int) $message->sender_id;
        $sendiri = $pengirim === $viewerId;

        return [
            'id' => $message->id,
            'body' => (string) $message->body,
            'image_url' => $message->image_path ? route('chat.image', $message->id) : null,
            'image_size' => $message->image_size,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name ?? 'Pengguna',
            'mine' => $sendiri,
            'can_edit' => $sendiri,
            'can_delete' => $sendiri || $viewerIsAdmin,
            'edit_url' => $sendiri ? route('chat.update', $message->id) : null,
            'delete_url' => ($sendiri || $viewerIsAdmin) ? route('chat.destroy', $message->id) : null,
            'edited' => $message->edited_at !== null,
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
