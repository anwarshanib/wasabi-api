<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\CardholderController;
use App\Http\Controllers\Api\V1\CommonController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WebhookEventController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use App\Http\Controllers\WebhookController;
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

/*
|--------------------------------------------------------------------------
| Wasabi Webhook Receiver
|--------------------------------------------------------------------------
| This endpoint receives async event notifications from Wasabi Card.
| It is intentionally outside the api.auth middleware — Wasabi does not
| send our X-API-KEY. Instead, the X-WSB-SIGNATURE RSA header is verified
| inside WebhookController to authenticate the request.
|
| Register this URL in the Wasabi merchant dashboard:
|   Account Settings → webhookUrl → https://yourdomain.com/api/webhook
*/
Route::post('webhook', [WebhookController::class, 'receive']);

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
        | WALLET — Deposit orders and transaction history (Deprecated)
        |----------------------------------------------------------------------
        */
        Route::prefix('wallet')->group(function (): void {
            Route::post('deposit',              [WalletController::class, 'walletDeposit']);
            Route::post('deposit/transactions', [WalletController::class, 'walletDepositTransactions']);
            Route::post('v2/coins',             [WalletController::class, 'coinListV2']);
            Route::post('v2/create',            [WalletController::class, 'createWalletAddressV2']);
            Route::post('v2/address-list',      [WalletController::class, 'walletAddressListV2']);
            Route::post('v2/transactions',      [WalletController::class, 'walletTransactionHistoryV2']);
        });

        /*
        |----------------------------------------------------------------------
        | CARD — Card types and card management
        |----------------------------------------------------------------------
        */
        Route::prefix('cards')->group(function (): void {
            Route::post('support-bins', [CardController::class, 'supportBins']);
            Route::post('create',       [CardController::class, 'createCardDeprecated']);
            Route::post('create-v2',    [CardController::class, 'createCardV2']);
            Route::post('info',         [CardController::class, 'cardInfo']);
            Route::post('sensitive',    [CardController::class, 'cardInfoForSensitive']);
            Route::post('balance',      [CardController::class, 'cardBalance']);
            Route::post('list',         [CardController::class, 'cardList']);
            Route::post('update',       [CardController::class, 'updateCard']);
            Route::post('note',         [CardController::class, 'updateNote']);
            Route::post('freeze',       [CardController::class, 'freezeCardV2']);
            Route::post('unfreeze',     [CardController::class, 'unfreezeCardV2']);
            Route::post('deposit',               [CardController::class, 'depositCard']);
            Route::post('withdraw',              [CardController::class, 'withdrawCard']);
            Route::post('cancel',                [CardController::class, 'cancelCard']);
            Route::post('activate-physical',          [CardController::class, 'activatePhysicalCard']);
            Route::post('update-pin',                  [CardController::class, 'updatePin']);
            Route::post('purchase-transactions',       [CardController::class, 'cardPurchaseTransactions']);
            Route::post('operation-transactions',      [CardController::class, 'cardOperationTransactions']);
            Route::post('operation-transactions-v2',   [CardController::class, 'cardOperationTransactionsV2']);
            Route::post('auth-transactions',           [CardController::class, 'cardAuthorizationTransactions']);
            Route::post('auth-fee-transactions',       [CardController::class, 'cardAuthFeeTransactions']);
            Route::post('3ds-transactions',            [CardController::class, 'card3dsTransactions']);
            Route::post('simulate-auth',               [CardController::class, 'simulateAuthTransaction']);
        });

        /*
        |----------------------------------------------------------------------
        | ACCOUNT — Assets and account management
        |----------------------------------------------------------------------
        */
        Route::prefix('accounts')->group(function (): void {
            Route::get('assets',       [AccountController::class, 'assets']);
            Route::get('single',       [AccountController::class, 'singleAccount']);
            Route::get('transactions', [AccountController::class, 'ledgerTransactions']);
            Route::post('create',      [AccountController::class, 'createSharedAccount']);
            Route::post('transfer',    [AccountController::class, 'fundTransfer']);
            Route::get('/',            [AccountController::class, 'accountList']);
        });

        /*
        |----------------------------------------------------------------------
        | WEBHOOK EVENTS — Poll stored Wasabi async event results
        |----------------------------------------------------------------------
        */
        Route::get('webhook-events', [WebhookEventController::class, 'index']);

        /*
        |----------------------------------------------------------------------
        | CARDHOLDER — Cardholder management
        |----------------------------------------------------------------------
        */
        Route::prefix('cardholders')->group(function (): void {
            Route::post('occupations',  [CardholderController::class, 'occupations']);
            Route::post('create',       [CardholderController::class, 'createCardholderDeprecated']);
            Route::post('update',       [CardholderController::class, 'updateCardholderDeprecated']);
            Route::post('create-v2',    [CardholderController::class, 'createCardholderV2']);
            Route::post('update-v2',    [CardholderController::class, 'updateCardholderV2']);
            Route::post('list',         [CardholderController::class, 'cardholderList']);
            Route::post('update-email', [CardholderController::class, 'updateCardholderEmail']);
        });

    });
