<?php
declare(strict_types=1);

defined('APP_NAME')  || define('APP_NAME',  getenv('APP_NAME')  ?: 'RePawter');
defined('APP_ENV')   || define('APP_ENV',   getenv('APP_ENV')   ?: 'dev');
defined('BASE_URL')  || define('BASE_URL',  getenv('BASE_URL')  ?: '/repawter');
defined('APP_TZ')    || define('APP_TZ',    getenv('APP_TZ')    ?: 'Asia/Manila');
defined('APP_DEBUG') || define('APP_DEBUG', APP_ENV === 'dev');

defined('DB_HOST')    || define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
defined('DB_NAME')    || define('DB_NAME',    getenv('DB_NAME')    ?: 'repawter');
defined('DB_USER')    || define('DB_USER',    getenv('DB_USER')    ?: 'YOUR_DB_USER');
defined('DB_PASS')    || define('DB_PASS',    getenv('DB_PASS')    ?: 'YOUR_DB_PASS');
defined('DB_CHARSET') || define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

defined('APP_ROOT')   || define('APP_ROOT',   dirname(__DIR__, 2));
defined('UPLOAD_DIR') || define('UPLOAD_DIR', APP_ROOT . '/uploads');
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_URL . '/uploads');

defined('LOG_DIR') || define('LOG_DIR', APP_ROOT . '/storage/logs');

defined('MAX_UPLOAD_BYTES')   || define('MAX_UPLOAD_BYTES',   5 * 1024 * 1024);
defined('ALLOWED_IMAGE_MIME') || define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);

defined('SESSION_SECURE') || define('SESSION_SECURE', APP_ENV === 'prod');

$localConfig = APP_ROOT . '/app/config/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}
