<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Documentation only
|--------------------------------------------------------------------------
| This project is API-only. The web router exists solely to serve the
| Swagger UI at /api/documentation (registered by l5-swagger).
| No other web routes should be added here.
*/

Route::redirect('/', '/api/documentation');
