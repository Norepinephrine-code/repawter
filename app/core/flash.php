<?php
declare(strict_types=1);

function flash(string $type, string $msg): void
{
    $allowed = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
    return $flashes;
}

function flash_old(array $input): void
{
    $_SESSION['_old'] = $input;
}

function old_input(): array
{
    $old = $_SESSION['_old'] ?? [];
    $_SESSION['_old'] = [];
    return $old;
}

function peek_old(): array
{
    return $_SESSION['_old'] ?? [];
}
