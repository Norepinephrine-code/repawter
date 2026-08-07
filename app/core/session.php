<?php
declare(strict_types=1);

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    
    
    ini_set('session.use_strict_mode', '1');

    
    $cookiePath = BASE_URL === '' ? '/' : BASE_URL;

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => defined('SESSION_SECURE') ? SESSION_SECURE : false,
    ]);
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function user_id(): ?int
{
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function user_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function session_regenerate(): void
{
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
}

function login_as(array $u): void
{
    session_regenerate();
    set_current_user($u);
}

function set_current_user(array $u): void
{
    $_SESSION['user'] = [
        'id'                => (int)$u['id'],
        'role'              => $u['role'],
        'full_name'         => $u['full_name'],
        'email'             => $u['email'],
        'barangay_id'       => isset($u['barangay_id']) ? (int)$u['barangay_id'] : null,
        'account_status'    => $u['account_status'],
        'organization_name' => $u['organization_name'] ?? null,
    ];
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}
