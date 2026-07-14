<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
}

function csrf_verify(): void
{
    $token    = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['_csrf'] ?? '';

    if (!hash_equals($expected, $token)) {
        flash('error', 'Invalid or expired form token. Please try again.');
        $ref = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/';
        header('Location: ' . $ref);
        exit;
    }
}
