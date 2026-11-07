<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Keamanan Akun</h2>
                        <p class="mt-1 text-sm text-gray-600">Ringkasan status keamanan akun SIJALAT Anda.</p>
                    </header>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-gray-500">Status email</dt>
                            <dd class="font-medium {{ $user->hasVerifiedEmail() ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum terverifikasi' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-gray-500">Login terakhir</dt>
                            <dd class="font-medium text-gray-900">
                                {{ $user->last_login_at?->copy()->locale('id')->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-gray-500">IP terakhir</dt>
                            <dd class="font-medium text-gray-900">{{ $user->last_login_ip ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
