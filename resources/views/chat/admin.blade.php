<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <title>SIJALAT - Chat Siswa</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="stylesheet" href="{{ asset('css/univ.css') }}">
        <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
    </head>
    <body>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <nav class="navbar">
                <a class="profile-edit" href="{{ route('profile.edit') }}">
                    <img src="{{ asset('svg/user_icon.svg') }}" alt="">
                    <p>{{ auth()->user()->name }} </p>
                </a>

                @include('partials.brand')
                <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
            </nav>

            @if (session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            <div class="chat-page">
                <div class="chat-page-head">
                    <div>
                        <h1><x-icon name="message-circle" size="21" /> Chat Siswa</h1>
                        <p>Balas pertanyaan siswa tentang peminjaman, pengembalian, dan kondisi alat.</p>
                    </div>
                    <span class="chat-status">{{ $students->sum('unread_count') }} pesan belum dibaca</span>
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

                <div class="chat-admin-layout {{ $active ? 'has-active' : '' }}"
                     id="chatRoot"
                     data-mode="{{ $active ? 'admin' : 'inbox' }}"
                     @if ($active)
                         data-poll-url="{{ route('admin.chat.messages', $active->id) }}"
                         data-send-url="{{ route('admin.chat.kirim', $active->id) }}"
                         data-after="{{ (int) ($messages->last()->id ?? 0) }}"
                     @endif>

                    <aside class="chat-inbox">
                        <div class="chat-inbox-head">
                            <div class="chat-inbox-title">
                                <h2><x-icon name="users" size="16" /> Percakapan</h2>
                                <span>{{ $students->count() }} siswa</span>
                            </div>

                            <label class="chat-search" for="chatSearch">
                                <x-icon name="search" size="14" />
                                <input type="search" id="chatSearch" placeholder="Cari nama, NIS, atau kelas..." autocomplete="off">
                            </label>
                        </div>

                        <div class="chat-contact-list" id="chatContacts">
                            @forelse ($students as $student)
                                @php
                                    $last = $student->last_message;
                                    $unread = (int) ($student->unread_count ?? 0);
                                    $preview = $last
                                        ? (((int) $last->sender_id === (int) $viewer->id ? 'Kamu: ' : '') . $last->body)
                                        : 'Belum ada pesan';
                                @endphp

                                <a class="chat-contact {{ $active && $active->id === $student->id ? 'active' : '' }}"
                                   href="{{ route('admin.chat.show', $student->id) }}"
                                   data-search="{{ \Illuminate\Support\Str::lower($student->name . ' ' . ($student->nis ?? '') . ' ' . ($student->kelas ?? '')) }}">
                                    <span class="chat-contact-avatar">{{ $initials($student->name) }}</span>

                                    <span class="chat-contact-main">
                                        <span class="chat-contact-row">
                                            <span class="chat-contact-name">{{ $student->name }}</span>
                                            @if ($last)
                                                <span class="chat-contact-time">{{ $last->created_at->copy()->timezone('Asia/Jakarta')->format('H.i') }}</span>
                                            @endif
                                        </span>
                                        <span class="chat-contact-meta">
                                            {{ $student->kelas ?: 'Tanpa kelas' }} &middot; {{ $student->nis ?: 'Tanpa NIS' }}
                                        </span>
                                        <span class="chat-contact-preview">{{ \Illuminate\Support\Str::limit($preview, 48) }}</span>
                                    </span>

                                    @if ($unread > 0)
                                        <span class="chat-unread">{{ $unread > 99 ? '99+' : $unread }}</span>
                                    @endif
                                </a>
                            @empty
                                <div class="chat-inbox-empty">
                                    <x-icon name="inbox" size="26" />
                                    <p>Belum ada siswa yang mengirim pesan.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="chat-inbox-empty" id="chatNoResult" hidden>
                            <x-icon name="search" size="26" />
                            <p>Tidak ada percakapan yang cocok.</p>
                        </div>
                    </aside>

                    <section class="chat-panel">
                        @if (! $active)
                            <div class="chat-panel-empty">
                                <div class="chat-empty-icon"><x-icon name="message-circle" size="30" /></div>
                                <h3>Pilih percakapan</h3>
                                <p>Klik salah satu siswa di daftar untuk membaca dan membalas pesannya.</p>
                            </div>
                        @else
                            <header class="chat-panel-head">
                                <a class="chat-back" href="{{ route('admin.chat.index') }}" title="Kembali ke daftar" aria-label="Kembali ke daftar">
                                    <x-icon name="arrow-left" size="16" />
                                </a>

                                <span class="chat-contact-avatar">{{ $initials($active->name) }}</span>

                                <div class="chat-panel-id">
                                    <h2>{{ $active->name }}</h2>
                                    <p>
                                        {{ $active->kelas ?: 'Tanpa kelas' }} &middot; NIS {{ $active->nis ?: '-' }}
                                    </p>
                                </div>
                            </header>

                            <div class="chat-stream" id="chatStream" data-last-date="{{ $messages->last()?->created_at?->timezone('Asia/Jakarta')->format('Y-m-d') }}">
                                @if ($messages->isEmpty())
                                    <div class="chat-empty-state" id="chatEmpty">
                                        <div class="chat-empty-icon"><x-icon name="message-circle" size="30" /></div>
                                        <h3>Belum ada pesan</h3>
                                        <p>Sapa siswa ini untuk memulai percakapan.</p>
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

                                    <div class="chat-row {{ $mine ? 'mine' : 'theirs' }}" data-id="{{ $message->id }}">
                                        @if (! $mine)
                                            <span class="chat-avatar">{{ $initials($message->sender->name ?? $active->name) }}</span>
                                        @endif

                                        <div class="chat-bubble">
                                            @if (! $mine)
                                                <span class="chat-sender">{{ $message->sender->name ?? $active->name }}</span>
                                            @endif
                                            <p>{{ $message->body }}</p>
                                            <span class="chat-time">{{ $waktu->format('H.i') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="chat-alert" id="chatAlert" role="alert"></div>

                            <form class="chat-form" id="chatForm" action="{{ route('admin.chat.kirim', $active->id) }}" method="POST">
                                @csrf
                                <div class="chat-input-wrap">
                                    <textarea id="chatInput"
                                              name="body"
                                              rows="1"
                                              maxlength="2000"
                                              placeholder="Tulis balasan untuk {{ $active->name }}..."
                                              autocomplete="off"
                                              required></textarea>
                                </div>
                                <button class="chat-send" type="submit" id="chatSend" title="Kirim pesan" aria-label="Kirim pesan">
                                    <x-icon name="send" size="18" />
                                </button>
                            </form>
                            <p class="chat-hint">Enter untuk kirim &middot; Shift + Enter untuk baris baru</p>
                        @endif
                    </section>
                </div>
            </div>
        </main>

        @include('partials.sidebar-admin')

        <script src="{{ asset('js/univ.js') }}"></script>
        <script src="{{ asset('js/chat.js') }}"></script>
    </body>
</html>
