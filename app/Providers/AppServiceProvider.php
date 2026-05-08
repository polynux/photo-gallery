<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('gallery-auth', function (Request $request): Limit {
            $accessCode = (string) ($request->route('access_code') ?? $request->input('access_code', ''));

            return Limit::perMinute(config('gallery.login_rate_limit'))
                ->by(Str::lower($accessCode) . '|' . $request->ip());
        });
    }
}
