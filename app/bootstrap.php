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
    ini_set('log_errors',     '1');
}

// Load core files in dependency order
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

// Autoload model classes from app/models/{ClassName}.php on first use.
// This means pages/actions can call e.g. UserModel::find() without a manual require.
spl_autoload_register(static function (string $class): void {
    $file = APP_ROOT . '/app/models/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

session_boot();
