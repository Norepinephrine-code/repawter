<?php
declare(strict_types=1);

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
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
