<?php
declare(strict_types=1);

const ROLE_RESIDENT = 'community_resident';
const ROLE_OFFICIAL = 'barangay_official';
const ROLE_WELFARE  = 'welfare_org';
const ROLE_ADMIN    = 'system_admin';

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/';
        redirect('/auth/login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    $role = user_role();
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        include APP_ROOT . '/403.php';
        exit;
    }
}

function can(string $ability): bool
{
    $role = user_role();
    if ($role === null) {
        return false;
    }

    $map = [
        'manage_reports'       => [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN],
        'review_foster'        => [ROLE_WELFARE, ROLE_OFFICIAL, ROLE_ADMIN],
        'review_adoption'      => [ROLE_WELFARE, ROLE_ADMIN],
        'manage_pets'          => [ROLE_WELFARE, ROLE_ADMIN],
        'manage_announcements' => [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN],
        'manage_users'         => [ROLE_OFFICIAL, ROLE_ADMIN],
        'edit_criteria'        => [ROLE_ADMIN],
        'view_analytics'       => [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN],
        'view_audit'           => [ROLE_ADMIN],
        'manage_resources'     => [ROLE_ADMIN, ROLE_OFFICIAL, ROLE_WELFARE],
        'track_cases'          => [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN],
    ];

    return in_array($role, $map[$ability] ?? [], true);
}
