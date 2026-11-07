<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

/**
 * Mencatat waktu dan alamat IP login terakhir.
 */
class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly();
    }
}
