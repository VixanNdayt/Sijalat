<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $allowedDomain = Str::lower((string) config('auth.student_email_domain', 'student.stembayo.sch.id'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'nis' => [
                Rule::requiredIf(fn () => $user->isStudent()),
                'nullable',
                'digits_between:1,30',
                Rule::unique(User::class, 'nis')->ignore($user->id),
            ],
            // Samakan dengan validasi registrasi agar kelas tidak bisa diisi
            // sembarang lewat halaman profil.
            'kelas' => [
                Rule::requiredIf(fn () => $user->isStudent()),
                'nullable',
                'string',
                'max:50',
                Rule::in(config('school.classes', [])),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
                function (string $attribute, mixed $value, Closure $fail) use ($user, $allowedDomain) {
                    if (! $user->isStudent()) {
                        return;
                    }

                    if (Str::lower((string) $value) === Str::lower($user->email)) {
                        return;
                    }

                    if (! Str::endsWith(Str::lower((string) $value), '@'.$allowedDomain)) {
                        $fail('Email siswa harus menggunakan domain @'.$allowedDomain.'.');
                    }
                },
            ],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pesan validasi agar sesuai dengan bahasa aplikasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi untuk akun siswa.',
            'nis.digits_between' => 'NIS harus berupa angka 1 sampai 30 digit.',
            'kelas.required' => 'Kelas wajib dipilih untuk akun siswa.',
            'kelas.in' => 'Kelas yang dipilih tidak valid. Pilih salah satu kelas yang tersedia.',
        ];
    }
}
