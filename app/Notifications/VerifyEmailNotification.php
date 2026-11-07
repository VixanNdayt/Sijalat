<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return (new MailMessage)
            ->subject('Verifikasi Email SIJALAT')
            ->greeting('Halo, ' . ($notifiable->name ?: 'Siswa') . '!')
            ->line('Terima kasih telah mendaftar di SIJALAT, Sistem Peminjaman Alat SIJA.')
            ->line('Klik tombol di bawah ini untuk memverifikasi alamat email kamu.')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Tautan verifikasi berlaku selama 60 menit.')
            ->line('Jika kamu tidak merasa membuat akun, abaikan email ini.')
            ->salutation('Salam, Tim SIJALAT');
    }
}
