<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/svg+xml" href="{{ asset('svg/logo-sijalat.svg') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SIJALAT - Profil</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            {{-- Fallback saat aset Vite belum di-build (npm run build) --}}
            <link rel="stylesheet" href="{{ asset('css/tailwind-fallback.css') }}">
        @endif
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            {{-- Alpine dipakai komponen Breeze (dropdown, modal). Saat aset Vite
                 sudah di-build, Alpine ikut dari resources/js/app.js. --}}
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>

            {{-- Toggle menu mobile sederhana, cadangan bila Alpine gagal dimuat --}}
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var toggle = document.getElementById('sijalat-nav-toggle');
                    var menu = document.getElementById('sijalat-nav-menu');

                    if (!toggle || !menu) {
                        return;
                    }

                    toggle.addEventListener('click', function () {
                        var isOpen = !menu.classList.contains('hidden');

                        menu.classList.toggle('hidden', isOpen);
                        menu.classList.toggle('block', !isOpen);
                        toggle.setAttribute('aria-expanded', String(!isOpen));
                    });
                });
            </script>
        @endunless
    </body>
</html>
