<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../sandbox_project/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Redirect root /sandbox and /sandbox/ directly — before Laravel boots,
// so stale config/route caches have no effect on this redirect.
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if ($requestUri === '/sandbox' || $requestUri === '/sandbox/' || $requestUri === '/sandbox/index.php') {
    header('Location: /sandbox/docs/guide', true, 302);
    exit;
}

// Strip /sandbox prefix from all relevant server variables.
// REQUEST_URI is stripped so Laravel routing matches existing routes.
// SCRIPT_NAME is overridden so Laravel's Request::root() does NOT
// auto-detect /sandbox — APP_URL in .env handles the prefix instead.
$prefix = '/sandbox';

foreach (['REQUEST_URI', 'PHP_SELF'] as $key) {
    if (isset($_SERVER[$key])) {
        if ($_SERVER[$key] === $prefix) {
            $_SERVER[$key] = '/';
        } elseif (str_starts_with($_SERVER[$key], $prefix.'/')) {
            $_SERVER[$key] = substr($_SERVER[$key], strlen($prefix));
        }
    }
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

if (isset($_SERVER['PATH_INFO']) && str_starts_with($_SERVER['PATH_INFO'], $prefix)) {
    $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], strlen($prefix)) ?: '/';
}

// Register the Composer autoloader...
require __DIR__.'/../sandbox_project/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../sandbox_project/bootstrap/app.php';

$app->handleRequest(Request::capture());
