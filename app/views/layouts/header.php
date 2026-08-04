<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '') ?><?= isset($title) && $title !== '' ? ' &middot; ' : '' ?><?= e(APP_NAME) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css">

    <link rel="stylesheet" href="<?= asset('/css/paw-theme.css') ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg paw-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">
            🐾 <?= e(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'home' ? ' active' : '' ?>"
                       href="<?= url('/') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'adoption' ? ' active' : '' ?>"
                       href="<?= url('/adoption/') ?>">Adoption Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'resources' ? ' active' : '' ?>"
                       href="<?= url('/resources/') ?>">Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'announcements' ? ' active' : '' ?>"
                       href="<?= url('/announcements/') ?>">Announcements</a>
                </li>
<?php if (is_logged_in()): ?>
<?php   $role = user_role(); ?>
<?php   if ($role === ROLE_RESIDENT): ?>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'report' ? ' active' : '' ?>"
                       href="<?= url('/reports/submit.php') ?>">Report an Animal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'my_reports' ? ' active' : '' ?>"
                       href="<?= url('/reports/') ?>">My Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'foster' ? ' active' : '' ?>"
                       href="<?= url('/foster/') ?>">Foster</a>
                </li>
<?php   endif; ?>
<?php   if (in_array($role, [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true)): ?>
                <li class="nav-item">
                    <a class="nav-link<?= ($activeNav ?? '') === 'admin' ? ' active' : '' ?>"
                       href="<?= url('/admin/dashboard.php') ?>">
                        <i class="bi bi-speedometer2"></i> Admin
                    </a>
                </li>
<?php   endif; ?>
<?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
<?php if (is_logged_in()): ?>

                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="<?= url('/notifications/') ?>" title="Notifications">
                        <i class="bi bi-bell-fill fs-5"></i>
<?php   $unread = unread_count(user_id()); ?>
<?php   if ($unread > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unread > 99 ? '99+' : $unread ?>
                            <span class="visually-hidden">unread notifications</span>
                        </span>
<?php   endif; ?>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <?= e(current_user()['full_name'] ?? '') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="<?= url('/profile/') ?>">
                            <i class="bi bi-person"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= url('/actions/auth/logout.php') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Logout
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
                <li class="nav-item">
                    <a class="btn btn-paw ms-2<?= ($activeNav ?? '') === 'register' ? ' active' : '' ?>"
                       href="<?= url('/auth/register.php') ?>">Register</a>
                </li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php foreach (get_flashes() as $flash): ?>
<?php
    $alertClass = match ($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
?>
<div class="container mt-2">
    <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
        <?= e($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endforeach; ?>

<main class="container py-4">
