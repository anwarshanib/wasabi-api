<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third-Party API Authentication Keys
    |--------------------------------------------------------------------------
    | Comma-separated list of valid API keys for third-party clients.
    | Clients must pass their key as the X-API-KEY request header.
    |
    | Example .env:
    |   API_KEYS=key_abc123,key_xyz789
    */
    'keys' => array_filter(
        array_map('trim', explode(',', (string) env('API_KEYS', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting — requests per minute per API key
    |--------------------------------------------------------------------------
    */
    'rate_limit' => (int) env('API_RATE_LIMIT', 60),

];
