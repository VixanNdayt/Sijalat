<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'nis', 'kelas'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Hanya kolom profil yang boleh diisi lewat mass assignment.
     * Role dan metadata login sengaja tidak termasuk agar tidak bisa
     * dinaikkan hak aksesnya lewat input pengguna.
     */
    protected $fillable = [
        'name',
        'nis',
        'kelas',
        'email',
        'password',
    ];
    public function pinjam()
    {
        return $this->hasMany(Pinjam::class); // Satu siswa bisa punya banyak peminjaman
    }

    public function pinjams()
    {
        return $this->hasMany(Pinjam::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    public function isAdmin()
    {
        return $this->role_user === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role_user !== 'admin';
    }

    public function hasCompletedProfile(): bool
    {
        return filled($this->nis) && filled($this->kelas);
    }

    /**
     * URL foto profil, atau null jika pengguna belum mengunggah foto.
     */
    public function profilePhotoUrl(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        // URL relatif terhadap host yang sedang dipakai agar tetap sama-origin
        // (lolos CSP) meski aplikasi dibuka lewat domain, IP LAN, atau localhost.
        return asset('storage/' . ltrim($this->profile_photo_path, '/'));
    }

    /**
     * Inisial nama untuk avatar cadangan.
     */
    public function initials(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $initials = mb_substr($parts[0], 0, 1);

        if (count($parts) > 1) {
            $initials .= mb_substr($parts[count($parts) - 1], 0, 1);
        }

        return mb_strtoupper($initials);
    }
}
