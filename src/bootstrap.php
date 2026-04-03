<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

\define('APP_BASE_PATH', dirname(__DIR__));

\App\Support\Env::load(dirname(__DIR__) . '/.env');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$storagePath = \App\Config::get('STORAGE_PATH', APP_BASE_PATH . '/storage');

if (!preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/])/', (string) $storagePath)) {
    $storagePath = APP_BASE_PATH . '/' . ltrim((string) $storagePath, '/\\');
}

if (!is_dir($storagePath)) {
    mkdir($storagePath, 0777, true);
}
