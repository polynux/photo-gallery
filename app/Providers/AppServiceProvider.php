<?php

namespace App\Providers;

use App\Services\ThumbnailService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(new Driver));
        $this->app->singleton(ThumbnailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('gallery-auth', function (Request $request): Limit {
            $accessCode = (string) ($request->route('access_code') ?? $request->input('access_code', ''));

            return Limit::perMinute(config('gallery.login_rate_limit'))
                ->by(Str::lower($accessCode).'|'.$request->ip());
        });
    }
}
