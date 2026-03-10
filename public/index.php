<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Corrige REQUEST_URI quando Apache serve via DirectoryIndex (evita 404 na raiz)
$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($uri === '/index.php' || $uri === '') {
    $_SERVER['REQUEST_URI'] = '/';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
