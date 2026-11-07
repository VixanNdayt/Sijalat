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
                'numeric',
                Rule::unique(User::class, 'nis')->ignore($user->id),
            ],
            'kelas' => ['nullable', 'string', 'max:50'],
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
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
