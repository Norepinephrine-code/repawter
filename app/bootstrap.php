<?php

declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/app/config/config.php';

date_default_timezone_set(APP_TZ);

error_reporting(E_ALL);

if (APP_ENV === 'dev') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    if (defined('LOG_DIR')) {
        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0775, true);
        }
        if (is_dir(LOG_DIR) && is_writable(LOG_DIR)) {
            ini_set('error_log', LOG_DIR . '/php-error.log');
        }
    }
}

require_once APP_ROOT . '/app/core/errors.php';
error_handlers_register();

if (!defined('APP_NO_OUTPUT_BUFFER')) {
    ob_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "font-src 'self' data:; "
        . "frame-src https://www.facebook.com https://web.facebook.com; "
        . "connect-src 'self'; object-src 'none'; base-uri 'self'; "
        . "form-action 'self'; frame-ancestors 'self'"
    );

    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

require_once APP_ROOT . '/app/core/db.php';
require_once APP_ROOT . '/app/core/session.php';
require_once APP_ROOT . '/app/core/flash.php';
require_once APP_ROOT . '/app/core/csrf.php';
require_once APP_ROOT . '/app/core/validation.php';
require_once APP_ROOT . '/app/core/view.php';
require_once APP_ROOT . '/app/core/auth.php';
require_once APP_ROOT . '/app/core/rbac.php';
require_once APP_ROOT . '/app/core/upload.php';
require_once APP_ROOT . '/app/core/pagination.php';
require_once APP_ROOT . '/app/core/notify.php';
require_once APP_ROOT . '/app/core/audit.php';

spl_autoload_register(static function (string $class): void {
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class)) {
        return;
    }
    $file = APP_ROOT . '/app/models/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

session_boot();
