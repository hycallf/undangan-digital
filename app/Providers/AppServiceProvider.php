<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Policies\EventPolicy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

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
        Vite::prefetch(concurrency: 3);
        URL::forceScheme('http');

        // Schema::defaultStringLength(191);
        // if (str_contains(config('app.url'), '.ngrok') || config('app.env') === 'production') {
        //     URL::forceScheme('https');

            // Force secure cookies untuk ngrok
            // config([
            //     'session.secure' => true,
            //     'session.same_site' => 'none', // Penting untuk ngrok
            // ]);
        }

    //     // Trust ngrok proxy headers
    //     if (str_contains(config('app.url'), '.ngrok')) {
    //         request()->server->set('HTTPS', 'on');
    //         request()->server->set('HTTP_X_FORWARDED_PROTO', 'https');
    //         request()->server->set('HTTP_X_FORWARDED_SSL', 'on');
    //     }

}
