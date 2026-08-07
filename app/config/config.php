<?php

declare(strict_types=1);

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__, 2));

function config_load_dotenv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($key === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
            continue;
        }

        
        $len = strlen($value);
        if ($len >= 2
            && (($value[0] === '"' && $value[$len - 1] === '"')
                || ($value[0] === "'" && $value[$len - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        
        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

config_load_dotenv(APP_ROOT . '/.env');

function config_env(string $key, string $default, bool $allowEmpty = false): string
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }
    if ($value === '' && !$allowEmpty) {
        return $default;
    }

    return $value;
}

function config_env_bool(string $key, bool $default): bool
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

defined('APP_NAME')  || define('APP_NAME',  config_env('APP_NAME', 'RePawter'));
defined('APP_ENV')   || define('APP_ENV',   config_env('APP_ENV',  'dev'));

defined('BASE_URL')  || define('BASE_URL',  config_env('BASE_URL', '/repawter', true));
defined('APP_TZ')    || define('APP_TZ',    config_env('APP_TZ',   'Asia/Manila'));
defined('APP_DEBUG') || define('APP_DEBUG', APP_ENV === 'dev');

defined('DB_HOST')    || define('DB_HOST',    config_env('DB_HOST',    '127.0.0.1'));
defined('DB_NAME')    || define('DB_NAME',    config_env('DB_NAME',    'repawter'));
defined('DB_USER')    || define('DB_USER',    config_env('DB_USER',    'root'));
defined('DB_PASS')    || define('DB_PASS',    config_env('DB_PASS',    '', true));
defined('DB_CHARSET') || define('DB_CHARSET', config_env('DB_CHARSET', 'utf8mb4'));

defined('UPLOAD_DIR') || define('UPLOAD_DIR', APP_ROOT . '/uploads');
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_URL . '/uploads');
defined('LOG_DIR')    || define('LOG_DIR',    APP_ROOT . '/storage/logs');

defined('MAX_UPLOAD_BYTES')   || define('MAX_UPLOAD_BYTES',   (int)config_env('MAX_UPLOAD_BYTES', (string)(5 * 1024 * 1024)));
defined('ALLOWED_IMAGE_MIME') || define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);

defined('SESSION_SECURE') || define('SESSION_SECURE', config_env_bool('SESSION_SECURE', APP_ENV === 'prod'));

defined('TRUST_PROXY')    || define('TRUST_PROXY',    config_env_bool('TRUST_PROXY', false));

$localConfig = APP_ROOT . '/app/config/config.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}
