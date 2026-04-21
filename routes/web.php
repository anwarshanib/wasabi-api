<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('docs.guide'));

/*
|--------------------------------------------------------------------------
| Developer Documentation (public — no auth required)
|--------------------------------------------------------------------------
| /docs/guide     → Integration guide (auth, errors, webhooks, quick-start)
| /docs/reference → Redoc-powered full API reference (reads OpenAPI JSON)
*/
Route::get('/docs/guide',     fn () => view('docs.guide'))->name('docs.guide');
Route::get('/docs/reference', fn () => view('docs.index'))->name('docs.reference');

/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
| Simple blade-based portal to manage third-party API tokens.
| Protected by session login (ADMIN_EMAIL / ADMIN_PASSWORD in .env).
|
| URL: /admin
*/
Route::prefix('admin')->name('admin.')->group(function (): void {

    // Login (unauthenticated)
    Route::get('login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');

    // Protected routes
    Route::middleware('admin.auth')->group(function (): void {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::prefix('tokens')->name('tokens.')->group(function (): void {
            Route::get('/',              [TokenController::class, 'index'])->name('index');
            Route::get('create',         [TokenController::class, 'create'])->name('create');
            Route::post('/',             [TokenController::class, 'store'])->name('store');
            Route::post('{token}/toggle',[TokenController::class, 'toggle'])->name('toggle');
            Route::post('{token}/reveal',[TokenController::class, 'reveal'])->name('reveal');
            Route::delete('{token}',     [TokenController::class, 'destroy'])->name('destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| TEMPORARY SETUP ROUTE — DELETE AFTER FIRST USE
|--------------------------------------------------------------------------
| Visit: https://wasabi.alphalinx.top/setup-run-x9k2m7
| IMPORTANT: Delete this route immediately after migrations complete.
*/
Route::get('/setup-run-x9k2m7', function () {
    $secret = request('secret');

    if ($secret !== env('SETUP_SECRET')) {
        abort(403, 'Forbidden');
    }

    $output = [];

    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $output[] = '✅ config:clear done';
    } catch (\Throwable $e) {
        $output[] = '❌ config:clear failed: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        $output[] = '✅ route:clear done';
    } catch (\Throwable $e) {
        $output[] = '❌ route:clear failed: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        $output[] = '✅ view:clear done';
    } catch (\Throwable $e) {
        $output[] = '❌ view:clear failed: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output[] = '✅ migrate: ' . trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $output[] = '❌ migrate failed: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('config:cache');
        $output[] = '✅ config:cache done — APP_URL = ' . config('app.url');
    } catch (\Throwable $e) {
        $output[] = '❌ config:cache failed: ' . $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('route:cache');
        $output[] = '✅ route:cache done';
    } catch (\Throwable $e) {
        $output[] = '❌ route:cache failed: ' . $e->getMessage();
    }

    return '<pre style="font-family:monospace;padding:20px">' . implode("\n", $output) . "\n\n⚠️  DELETE THIS ROUTE FROM routes/web.php NOW</pre>";
});
