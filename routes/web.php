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

Route::redirect('/', '/docs/guide');

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
