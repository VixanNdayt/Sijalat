<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'nis', 'kelas'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'email_verified_at' => 'datetime',
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
     * Gunakan notifikasi verifikasi berbahasa Indonesia dengan tampilan SIJALAT.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}

