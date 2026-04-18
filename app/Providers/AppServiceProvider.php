<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        /*
        |----------------------------------------------------------------------
        | Rate Limiter — "client"
        |----------------------------------------------------------------------
        | Applied to all /api/v1/* routes via throttle:client middleware.
        | Keyed by X-API-KEY so each third-party client has its own bucket.
        */
        RateLimiter::for('client', function (Request $request): Limit {
            return Limit::perMinute(config('api.rate_limit', 60))
                ->by($request->header('X-API-KEY') ?? $request->ip());
        });
    }
}

