<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="description" content="<?= e($metaDescription ?? 'RePawter connects residents, barangay officials and animal-welfare organizations to report stray animals, coordinate rescues, and find homes for pets.') ?>">
    <title><?= e($title ?? '') ?><?= isset($title) && $title !== '' ? ' &middot; ' : '' ?><?= e(APP_NAME) ?></title>

    <link rel="icon" href="<?= asset('/img/favicon.svg') ?>" type="image/svg+xml">

    <link rel="stylesheet" href="<?= asset('/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/css/paw-theme.css') ?>">
</head>
<body>

<a class="paw-skip-link" href="#main-content">Skip to main content</a>

<nav class="navbar navbar-expand-xl paw-navbar" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="<?= url('/') ?>">
            <span aria-hidden="true">🐾</span> <?= e(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'home' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'home' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'adoption' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'adoption' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/adoption/') ?>">Adoption Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'resources' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'resources' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/resources/') ?>">Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'announcements' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'announcements' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/announcements/') ?>">Announcements</a>
                </li>
<?php if (is_logged_in()): ?>
<?php   $role = user_role(); ?>
<?php   if ($role === ROLE_RESIDENT): ?>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'report' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'report' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/reports/submit.php') ?>">Report an Animal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'my_reports' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'my_reports' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/reports/') ?>">My Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'foster' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'foster' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/foster/') ?>">Foster</a>
                </li>
<?php   endif; ?>
<?php   if (in_array($role, [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true)): ?>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'admin' ? ' active' : '' ?>"
                       <?= ($activeNav ?? '') === 'admin' ? 'aria-current="page"' : '' ?>
                       href="<?= url('/admin/dashboard.php') ?>">
                        <i class="bi bi-speedometer2" aria-hidden="true"></i> Admin
                    </a>
                </li>
<?php   endif; ?>
<?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-xl-0 align-items-xl-center">
<?php if (is_logged_in()): ?>
<?php   $unread = unread_count(user_id()); ?>
                <li class="nav-item me-xl-1">
                    <a class="nav-link position-relative" href="<?= url('/notifications/') ?>">
                        <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
<?php   if ($unread > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unread > 99 ? '99+' : $unread ?>
                        </span>
<?php   endif; ?>
                        <span class="d-xl-none ms-2">Notifications</span>
                        <span class="visually-hidden">
                            Notifications<?= $unread > 0 ? " ({$unread} unread)" : '' ?>
                        </span>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <?= e(current_user()['full_name'] ?? '') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="<?= url('/profile/') ?>">
                            <i class="bi bi-person" aria-hidden="true"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= url('/actions/auth/logout.php') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
<?php else: ?>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'login' ? ' active' : '' ?>"
                       href="<?= url('/auth/login.php') ?>">Login</a>
                </li>
                <li class="nav-item mt-2 mt-xl-0">
                    <a class="btn btn-paw ms-xl-2" href="<?= url('/auth/register.php') ?>">Register</a>
                </li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php $flashes = get_flashes(); ?>
<?php if ($flashes !== []): ?>
<div class="container mt-3 paw-flash-region" role="status" aria-live="polite">
<?php   foreach ($flashes as $flash): ?>
<?php
        $alertClass = match ($flash['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        $alertIcon = match ($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'error'   => 'bi-exclamation-octagon-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            default   => 'bi-info-circle-fill',
        };
?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi <?= $alertIcon ?> mt-1" aria-hidden="true"></i>
        <div class="flex-grow-1"><?= e($flash['msg']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
    </div>
<?php   endforeach; ?>
</div>
<?php endif; ?>

<main class="container py-4" id="main-content">
