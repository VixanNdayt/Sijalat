<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - Chat Admin</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}?v=3">
        <link rel="stylesheet" href="{{ asset('css/chat.css') }}?v=3">
        @include('partials.theme-head')
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <x-user-avatar :user="auth()->user()" />
                    <p>{{ auth()->user()->name }} </p>
                </a>

                @include('partials.brand')
                @include('partials.navbar-actions')
            </nav>

            @if (session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            <div class="chat-page">
                <div class="chat-page-head">
                    <div>
                        <h1><x-icon name="message-circle" size="21" /> Chat Admin</h1>
                        <p>
                            Tanyakan kendala peminjaman, jadwal pengembalian, atau kondisi alat.
                            Pesan akan dibalas oleh guru / admin.
                        </p>
                    </div>
                    <span class="chat-status">Admin siap membantu</span>
                </div>

                @php
                    $viewer = auth()->user();
                    $initials = function ($name) {
                        $parts = \Illuminate\Support\Str::of((string) $name)->explode(' ')->filter()->take(2);

                        return \Illuminate\Support\Str::upper(
                            $parts->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('')
                        );
                    };
                    $lastDate = null;
                @endphp

                <section class="chat-shell"
                         id="chatRoot"
                         data-mode="student"
                         data-poll-url="{{ route('chat.messages') }}"
                         data-send-url="{{ route('chat.kirim') }}"
                         data-after="{{ (int) ($messages->last()->id ?? 0) }}">

                    <header class="chat-shell-head">
                        <span class="chat-head-avatar"><x-icon name="users" size="19" /></span>
                        <div>
                            <h2>Guru / Admin SIJALAT</h2>
                            <p>Balasan dikirim pada jam kerja sekolah.</p>
                        </div>
                        <span class="chat-online">Aktif</span>

                        @if ($messages->isNotEmpty())
                            <button class="chat-clear"
                                    type="button"
                                    id="chatClear"
                                    data-clear-url="{{ route('chat.clear') }}"
                                    data-konfirmasi="Hapus seluruh riwayat chat? Tindakan ini tidak bisa dibatalkan."
                                    title="Hapus semua chat"
                                    aria-label="Hapus semua chat">
                                <x-icon name="trash" size="14" />
                            </button>
                        @endif
                    </header>

                    <div class="chat-stream" id="chatStream" data-last-date="{{ $messages->last()?->created_at?->timezone('Asia/Jakarta')->format('Y-m-d') }}">
                        @if ($messages->isEmpty())
                            <div class="chat-empty-state" id="chatEmpty">
                                <div class="chat-empty-icon"><x-icon name="message-circle" size="30" /></div>
                                <h3>Belum ada percakapan</h3>
                                <p>Mulai dengan menyapa admin dan jelaskan kendala peminjamanmu.</p>
                            </div>
                        @endif

                        @foreach ($messages as $message)
                            @php
                                $waktu = $message->created_at->copy()->timezone('Asia/Jakarta');
                                $tanggal = $waktu->format('Y-m-d');
                                $mine = (int) $message->sender_id === (int) $viewer->id;
                            @endphp

                            @if ($tanggal !== $lastDate)
                                <div class="chat-day" data-day="{{ $tanggal }}">
                                    <span>{{ $waktu->locale('id')->translatedFormat('l, d F Y') }}</span>
                                </div>
                                @php($lastDate = $tanggal)
                            @endif

                            <div class="chat-row {{ $mine ? 'mine' : 'theirs' }}"
                                 data-id="{{ $message->id }}"
                                 data-edit-url="{{ $mine ? route('chat.update', $message->id) : '' }}"
                                 data-delete-url="{{ $mine ? route('chat.destroy', $message->id) : '' }}">
                                @if (! $mine)
                                    <span class="chat-avatar">{{ $initials($message->sender->name ?? 'Admin') }}</span>
                                @endif

                                <div class="chat-bubble">
                                    @if (! $mine)
                                        <span class="chat-sender">{{ $message->sender->name ?? 'Admin' }}</span>
                                    @endif
                                    @if ($message->image_path)
                                        <a class="chat-image-link"
                                           href="{{ route('chat.image', $message->id) }}"
                                           target="_blank"
                                           rel="noopener">
                                            <img class="chat-image"
                                                 src="{{ route('chat.image', $message->id) }}"
                                                 alt="Lampiran gambar"
                                                 loading="lazy">
                                        </a>
                                    @endif
                                    @if (filled($message->body))
                                        <p>{{ $message->body }}</p>
                                    @endif
                                    <span class="chat-time">{{ $waktu->format('H.i') }}@if ($message->edited_at) &middot; diedit @endif</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="chat-alert" id="chatAlert" role="alert"></div>

                    <form class="chat-form" id="chatForm" action="{{ route('chat.kirim') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <button class="chat-attach" type="button" id="chatAttach" title="Lampirkan gambar" aria-label="Lampirkan gambar">
                            <x-icon name="image" size="18" />
                        </button>
                        <input type="file"
                               id="chatImageInput"
                               name="image"
                               accept="image/jpeg,image/png,image/webp"
                               hidden>

                        <div class="chat-input-wrap">
                            <div class="chat-preview" id="chatPreview" hidden>
                                <img id="chatPreviewImg" alt="Pratinjau lampiran">
                                <span class="chat-preview-size" id="chatPreviewSize"></span>
                                <button class="chat-preview-remove" type="button" id="chatPreviewRemove" title="Batalkan lampiran" aria-label="Batalkan lampiran">
                                    <x-icon name="x" size="13" />
                                </button>
                            </div>

                            <textarea id="chatInput"
                                      name="body"
                                      rows="1"
                                      maxlength="2000"
                                      placeholder="Tulis pesan untuk admin..."
                                      autocomplete="off">{{ $prefill ?? '' }}</textarea>
                        </div>
                        <button class="chat-send" type="submit" id="chatSend" title="Kirim pesan" aria-label="Kirim pesan">
                            <x-icon name="send" size="18" />
                        </button>
                    </form>
                    <p class="chat-hint">Enter untuk kirim &middot; Shift + Enter untuk baris baru &middot; Klik kanan pesan untuk edit / hapus</p>
                </section>
            </div>
        </main>

        @include('partials.sidebar-siswa')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/chat.js') }}?v=3"></script>
    </body>
</html>
