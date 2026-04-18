<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Credentials
    |--------------------------------------------------------------------------
    | Single-account admin login for the token management portal.
    | Set these in .env — never commit real credentials.
    */
    'email'    => env('ADMIN_EMAIL', 'admin@example.com'),
    'password' => env('ADMIN_PASSWORD', 'changeme'),

];
