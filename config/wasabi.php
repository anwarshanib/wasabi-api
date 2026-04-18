<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Wasabi Card API Base URL
    |--------------------------------------------------------------------------
    | Sandbox: https://sandbox-api-merchant.wasabicard.com
    | Production: update WASABI_BASE_URL in .env
    */
    'base_url' => env('WASABI_BASE_URL', 'https://sandbox-api-merchant.wasabicard.com'),

    /*
    |--------------------------------------------------------------------------
    | Merchant API Key
    |--------------------------------------------------------------------------
    | Unique identifier for the merchant. Sent as X-WSB-API-KEY on every request.
    */
    'api_key' => env('WASABI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | RSA Key File Paths
    |--------------------------------------------------------------------------
    | Store PEM files in storage/app/keys/ (gitignored).
    |
    | private_key_path   — Your merchant RSA private key (signs outgoing requests)
    | wsb_public_key_path — Wasabi platform RSA public key (verifies incoming responses)
    */
    'private_key_path'    => env('WASABI_PRIVATE_KEY_PATH', storage_path('app/keys/private.pem')),
    'wsb_public_key_path' => env('WASABI_WSB_PUBLIC_KEY_PATH', storage_path('app/keys/wsb_public.pem')),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('WASABI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Reference Data Cache TTL (seconds)
    |--------------------------------------------------------------------------
    | Wasabi docs recommend localising these; they rarely change.
    */
    'cache' => [
        'regions'      => (int) env('WASABI_CACHE_REGIONS', 86400),       // 24 h
        'cities'       => (int) env('WASABI_CACHE_CITIES', 86400),        // 24 h
        'mobile_codes' => (int) env('WASABI_CACHE_MOBILE_CODES', 86400),  // 24 h
    ],

];
