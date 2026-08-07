<?php

declare(strict_types=1);

function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (defined('TRUST_PROXY') && TRUST_PROXY) {
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strtolower((string)$proto) === 'https') {
            return true;
        }
    }

    return false;
}

function error_handlers_register(): void
{
    set_exception_handler(static function (Throwable $e): void {
        error_log_throwable($e);
        render_error_page($e);
    });

    register_shutdown_function(static function (): void {
        $last = error_get_last();
        if ($last === null) {
            return;
        }
        if (!in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }

        render_error_page(new ErrorException(
            $last['message'],
            0,
            $last['type'],
            $last['file'],
            $last['line']
        ));
    });
}

function error_log_throwable(Throwable $e): void
{
    error_log(sprintf(
        '%s: %s in %s:%d%s%s',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        PHP_EOL,
        $e->getTraceAsString()
    ));
}

function render_error_page(Throwable $e): void
{
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $showDetail = defined('APP_ENV') && APP_ENV === 'dev';
    $home       = defined('BASE_URL') ? BASE_URL . '/' : '/';
    $esc        = static fn (string $s): string
        => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Something went wrong</title><style>'
        . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'background:#FFF8F1;color:#3F2A1D;font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif}'
        . '.box{max-width:38rem;padding:2.5rem;text-align:center}'
        . 'h1{font-size:1.6rem;margin:0 0 .5rem}p{color:#6F5647;margin:0 0 1.5rem}'
        . 'a{display:inline-block;padding:.65rem 1.4rem;border-radius:.6rem;background:#E2673C;'
        . 'color:#fff;text-decoration:none;font-weight:600}'
        . 'pre{margin-top:2rem;padding:1rem;background:#2b1d15;color:#ffd9c7;border-radius:.6rem;'
        . 'text-align:left;overflow:auto;font-size:.8rem;line-height:1.5}'
        . '</style></head><body><div class="box">'
        . '<div style="font-size:3rem" aria-hidden="true">&#128062;</div>'
        . '<h1>Something went wrong</h1>'
        . '<p>We hit an unexpected problem handling that request. '
        . 'The issue has been logged and nothing you submitted was lost.</p>'
        . '<a href="' . $esc($home) . '">Back to home</a>';

    if ($showDetail) {
        echo '<pre>' . $esc($e::class . ': ' . $e->getMessage()) . "\n\n"
            . $esc($e->getFile() . ':' . $e->getLine()) . "\n\n"
            . $esc($e->getTraceAsString()) . '</pre>';
    }

    echo '</div></body></html>';
}
