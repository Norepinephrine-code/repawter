<?php
declare(strict_types=1);

defined('APP_NAME')  || define('APP_NAME',  'RePawter');
defined('APP_ENV')   || define('APP_ENV',   'dev');
defined('BASE_URL')  || define('BASE_URL',  '/repawter');

defined('DB_HOST')    || define('DB_HOST',    '127.0.0.1');
defined('DB_NAME')    || define('DB_NAME',    'repawter');
defined('DB_USER')    || define('DB_USER',    'YOUR_DB_USER');
defined('DB_PASS')    || define('DB_PASS',    'YOUR_DB_PASS');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

defined('APP_ROOT')   || define('APP_ROOT',   dirname(__DIR__, 2));
defined('UPLOAD_DIR') || define('UPLOAD_DIR', APP_ROOT . '/uploads');
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_URL . '/uploads');

defined('MAX_UPLOAD_BYTES')    || define('MAX_UPLOAD_BYTES',    5 * 1024 * 1024);
defined('ALLOWED_IMAGE_MIME')  || define('ALLOWED_IMAGE_MIME',  ['image/jpeg', 'image/png', 'image/webp']);

defined('APP_TZ') || define('APP_TZ', 'Asia/Manila');
