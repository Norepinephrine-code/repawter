<?php
declare(strict_types=1);

function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return BASE_URL . $path;
}

function asset(string $path): string
{
    return BASE_URL . '/assets' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function layout_header(string $title = '', string $activeNav = ''): void
{
    include APP_ROOT . '/app/views/layouts/header.php';
}

function layout_footer(array $extraJs = []): void
{

    old_input();
    include APP_ROOT . '/app/views/layouts/footer.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    include APP_ROOT . '/app/views/partials/' . $name . '.php';
}

function admin_nav(string $active = ''): void
{
    include APP_ROOT . '/app/views/layouts/admin_nav.php';
}
