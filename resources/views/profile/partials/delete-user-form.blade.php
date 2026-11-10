<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Setelah akun dihapus, seluruh data peminjaman dan informasi akun akan hilang permanen.
        </p>
    </header>

    <x-danger-button
        type="button"
        data-modal-open="confirm-user-deletion"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Yakin ingin menghapus akun?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Semua data akun akan dihapus permanen. Masukkan password untuk mengonfirmasi.
            </p>

            @if ($errors->userDeletion->has('akun'))
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->userDeletion->first('akun') }}
                </div>
            @endif

            <div class="mt-6">
                <x-input-label for="password" value="Password" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Password"
                    data-modal-autofocus
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" data-modal-close>
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
