<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

http_response_code(403);
layout_header('Access Denied', '');
?>
<div class="text-center py-5">
    <i class="bi bi-shield-lock-fill text-paw" style="font-size: 4rem;"></i>
    <h1 class="mt-3 paw-page-title">403 — Access Denied</h1>
    <p class="text-muted fs-5 mb-4">
        You don't have permission to view this page.
        Please log in with an account that has the required role.
    </p>
    <a href="<?= url('/') ?>" class="btn btn-paw me-2">
        <i class="bi bi-house-fill"></i> Go Home
    </a>
<?php if (!is_logged_in()): ?>
    <a href="<?= url('/auth/login.php') ?>" class="btn btn-paw-outline">
        <i class="bi bi-box-arrow-in-right"></i> Login
    </a>
<?php endif; ?>
</div>
<?php
layout_footer();
