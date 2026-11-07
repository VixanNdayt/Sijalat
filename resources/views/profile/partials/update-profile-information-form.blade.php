<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Perbarui nama, NIS, kelas, dan email akun Anda. Kelas dipakai untuk mengisi form pengambilan alat secara otomatis.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="profile_photo" :value="__('Foto Profil')" />

            <div class="mt-2 flex items-center gap-4">
                <x-user-avatar :user="$user" class="profile-avatar-preview" />

                <div class="min-w-0 flex-1">
                    <input
                        id="profile_photo"
                        name="profile_photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-700 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-800"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        Format JPG, PNG, atau WebP. Maksimal 2 MB. Jika tidak mengunggah foto, avatar otomatis memakai inisial nama.
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />

                    @if ($user->profile_photo_path)
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span>Hapus foto profil saat ini</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('remove_photo')" />
                    @endif
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Nama')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="nis" :value="__('NIS')" />
            <x-text-input id="nis" name="nis" type="text" inputmode="numeric" class="mt-1 block w-full" :value="old('nis', $user->nis)" :required="$user->isStudent()" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('nis')" />
        </div>

        <div>
            <x-input-label for="kelas" :value="__('Kelas')" />
            <x-text-input id="kelas" name="kelas" type="text" class="mt-1 block w-full" :value="old('kelas', $user->kelas)" placeholder="Contoh: XI SIJA A" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('kelas')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="username"
            />
            <p class="mt-1 text-xs text-gray-500">
                Jika diubah, email siswa harus memakai domain &#64;{{ config('auth.student_email_domain') }}.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
