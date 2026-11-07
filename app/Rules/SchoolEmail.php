<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Memastikan email memakai domain sekolah yang diizinkan.
 */
class SchoolEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower((string) config('auth.student_email_domain', 'student.stembayo.sch.id'));

        if (! is_string($value) || ! Str::endsWith(Str::lower(trim($value)), '@'.$domain)) {
            $fail('Email harus menggunakan domain @'.$domain.'.');
        }
    }
}
