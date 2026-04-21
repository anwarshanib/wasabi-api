<?php
// One-time setup/diagnostic script — DELETE THIS FILE after use.
if (($_GET['s'] ?? '') !== 'wsbSetup@2026!') {
    http_response_code(403);
    die('Forbidden');
}

$mode   = $_GET['mode'] ?? 'fix';
$base   = __DIR__ . '/../sandbox_project/';
$output = [];

// --- Always show diagnostics ---
$output[] = '── PHP version: ' . PHP_VERSION;

// .env APP_URL
$envFile = $base . '.env';
$envUrl  = '(not found)';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        if (str_starts_with(trim($line), 'APP_URL=')) {
            $envUrl = trim(substr(trim($line), 8));
            break;
        }
    }
}
$output[] = '── .env APP_URL: ' . $envUrl;

// cached config APP_URL
$cachedCfg = $base . 'bootstrap/cache/config.php';
if (file_exists($cachedCfg)) {
    $cfg = require $cachedCfg;
    $output[] = '── cached config APP_URL: ' . ($cfg['app']['url'] ?? '(missing)');
} else {
    $output[] = '── cached config APP_URL: (no config.php cache)';
}

// cached routes file
$routeCache = glob($base . 'bootstrap/cache/routes*.php') ?: [];
$output[] = '── cached route files: ' . (count($routeCache) ? implode(', ', array_map('basename', $routeCache)) : 'none');

// DB config from .env
$dbName = $dbUser = $dbHost = '(not found)';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if (str_starts_with($line, 'DB_DATABASE=')) $dbName = substr($line, 12);
        if (str_starts_with($line, 'DB_USERNAME=')) $dbUser = substr($line, 12);
        if (str_starts_with($line, 'DB_HOST='))     $dbHost = substr($line, 8);
    }
}
$output[] = '── .env DB_HOST:     ' . $dbHost;
$output[] = '── .env DB_DATABASE: ' . $dbName;
$output[] = '── .env DB_USERNAME: ' . $dbUser;

if ($mode === 'info') {
    echo '<pre style="font-family:monospace;padding:20px;background:#111;color:#0f0">';
    echo implode("\n", $output);
    echo "\n\nTo run fix: <a href='?s=wsbSetup@2026!&mode=fix' style='color:#0ff'>?mode=fix</a>";
    echo '</pre>';
    exit;
}

// --- FIX mode: clear caches then bootstrap Laravel ---
$output[] = '';
$output[] = '── Clearing bootstrap/cache/ ...';
$cacheDir = $base . 'bootstrap/cache/';
foreach (glob($cacheDir . '*.php') ?: [] as $file) {
    @unlink($file);
    $output[] = '   deleted: ' . basename($file);
}

$output[] = '';
$output[] = '── Bootstrapping Laravel (PHP ' . PHP_VERSION . ') ...';

// Override server vars so Laravel thinks it's inside /sandbox
$_SERVER['REQUEST_URI']  = '/';
$_SERVER['SCRIPT_NAME']  = '/index.php';
$_SERVER['PHP_SELF']     = '/index.php';
$_SERVER['HTTP_HOST']    = $_SERVER['HTTP_HOST'] ?? 'wasabi.alphalinx.top';

try {
    require $base . 'vendor/autoload.php';
    $app = require_once $base . 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $output[] = '── Bootstrap OK';
} catch (\Throwable $e) {
    $output[] = '❌ Bootstrap FAILED: ' . $e->getMessage();
    $output[] = '   File: ' . $e->getFile() . ':' . $e->getLine();
    echo '<pre style="font-family:monospace;padding:20px;background:#111;color:#f66">';
    echo implode("\n", $output);
    echo '</pre>';
    exit;
}

$output[] = '── config(app.url) before cache: ' . config('app.url');

foreach ([
    ['migrate',      ['--force' => true]],
    ['config:cache', []],
    ['route:cache',  []],
    ['view:clear',   []],
] as [$cmd, $args]) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd, $args);
        $result = trim(Illuminate\Support\Facades\Artisan::output()) ?: '(done)';
        $output[] = "✅ {$cmd}: {$result}";
    } catch (\Throwable $e) {
        $output[] = "❌ {$cmd} FAILED: " . $e->getMessage();
    }
}

$output[] = '';
$output[] = '── APP_URL after config:cache: ' . config('app.url');
$output[] = '';
$output[] = '⚠️  DELETE cc.php FROM THE SERVER NOW';
$output[] = 'Then test: <a href="/sandbox/admin/login" style="color:#0ff">/sandbox/admin/login</a>';

echo '<pre style="font-family:monospace;padding:20px;background:#111;color:#0f0">';
echo implode("\n", $output);
echo '</pre>';
