<?php

namespace App\Providers;

use App\Listeners\RecordUserLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // Password minimal 10 karakter dengan huruf besar, huruf kecil, dan angka.
        Password::defaults(fn () => Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers());

        Event::listen(Login::class, RecordUserLogin::class);
    }
}
