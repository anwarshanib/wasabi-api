<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Force the URL generator to use APP_URL as the root.
        // This is required for the sandbox deployment where requests arrive
        // at /sandbox/ but REQUEST_URI is stripped — without this, route()
        // and url() produce https://wasabi.alphalinx.top/... instead of
        // https://wasabi.alphalinx.top/sandbox/...
        URL::forceRootUrl(config('app.url'));

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

