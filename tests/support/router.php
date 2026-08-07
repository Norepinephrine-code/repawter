<?php

declare(strict_types=1);

const PROJECT_ROOT = __DIR__ . '/../..';

$base = getenv('BASE_URL');
if ($base === false) {
    $base = '/repawter';
}
$base = rtrim($base, '/');

$uri  = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = $uri;

if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
if ($path === '' || $path === false) {
    $path = '/';
}

$root   = realpath(PROJECT_ROOT);
$target = realpath($root . '/' . ltrim(rawurldecode($path), '/'));

if ($target === false || !str_starts_with($target, $root)) {
    router_not_found($base);
}

if (is_dir($target)) {
    $index = $target . '/index.php';
    if (!is_file($index)) {
        router_not_found($base);
    }
    $target = $index;
}

if (!is_file($target)) {
    router_not_found($base);
}

if (str_ends_with($target, '.php')) {
    
    
    $scriptName = $base . '/' . ltrim(str_replace($root, '', $target), '/');
    $_SERVER['SCRIPT_FILENAME'] = $target;
    $_SERVER['SCRIPT_NAME']     = $scriptName;
    $_SERVER['PHP_SELF']        = $scriptName;

    require $target;
    return true;
}

router_serve_static($target);

function router_serve_static(string $file): never
{
    $types = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'json'  => 'application/json; charset=UTF-8',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'pdf'   => 'application/pdf',
        'txt'   => 'text/plain; charset=UTF-8',
    ];

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

function router_not_found(string $base): never
{
    http_response_code(404);

    $page = realpath(PROJECT_ROOT . '/404.php');
    if ($page !== false && is_file($page)) {
        $_SERVER['SCRIPT_FILENAME'] = $page;
        $_SERVER['SCRIPT_NAME']     = $base . '/404.php';
        require $page;
        exit;
    }

    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 Not Found\n";
    exit;
}
