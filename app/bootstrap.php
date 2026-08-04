<?php
declare(strict_types=1);

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/config/config.php';

date_default_timezone_set(APP_TZ);

if (APP_ENV === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    if (defined('LOG_DIR') && !is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0775, true);
    }
    if (defined('LOG_DIR') && is_dir(LOG_DIR) && is_writable(LOG_DIR)) {
        ini_set('error_log', LOG_DIR . '/php-error.log');
    }
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

require APP_ROOT . '/app/core/db.php';
require APP_ROOT . '/app/core/session.php';
require APP_ROOT . '/app/core/flash.php';
require APP_ROOT . '/app/core/csrf.php';
require APP_ROOT . '/app/core/validation.php';
require APP_ROOT . '/app/core/view.php';
require APP_ROOT . '/app/core/auth.php';
require APP_ROOT . '/app/core/rbac.php';
require APP_ROOT . '/app/core/upload.php';
require APP_ROOT . '/app/core/pagination.php';
require APP_ROOT . '/app/core/notify.php';
require APP_ROOT . '/app/core/audit.php';

spl_autoload_register(static function (string $class): void {
    $file = APP_ROOT . '/app/models/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

session_boot();
