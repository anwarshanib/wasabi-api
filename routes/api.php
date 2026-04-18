<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CommonController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Wasabi Card Integration
|--------------------------------------------------------------------------
| All routes are versioned under /api/v1 and protected by API key auth.
| Rate limiting is applied per API key (configured in AppServiceProvider).
|
| Upstream: Wasabi Card Open API  (sandbox-api-merchant.wasabicard.com)
| Consumer: Third-party clients   (authenticated via X-API-KEY header)
*/

Route::prefix('v1')
    ->middleware(['api.auth', 'throttle:client'])
    ->group(function (): void {

        /*
        |----------------------------------------------------------------------
        | COMMON — Reference data (regions, cities, mobile codes, etc.)
        |----------------------------------------------------------------------
        */
        Route::prefix('common')->group(function (): void {
            Route::get('regions',              [CommonController::class, 'regions']);
            Route::get('cities',               [CommonController::class, 'cities']);
            Route::get('cities/hierarchical',  [CommonController::class, 'citiesHierarchical']);
            Route::get('mobile-codes',         [CommonController::class, 'mobileCodes']);
            Route::post('files/upload',        [CommonController::class, 'uploadFile']);
        });

        /*
        |----------------------------------------------------------------------
        | WORK ORDERS — Submit and query platform work orders
        |----------------------------------------------------------------------
        */
        Route::prefix('work-orders')->group(function (): void {
            Route::post('/',  [WorkOrderController::class, 'submitWorkOrder']);
            Route::get('/',   [WorkOrderController::class, 'listWorkOrders']);
        });

        /*
        |----------------------------------------------------------------------
        | ACCOUNT — Assets and account management
        |----------------------------------------------------------------------
        */
        Route::prefix('accounts')->group(function (): void {
            Route::get('assets', [AccountController::class, 'assets']);
            Route::get('/',      [AccountController::class, 'accountList']);
        });

    });
