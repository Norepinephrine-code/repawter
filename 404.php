<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

http_response_code(404);
layout_header('Page Not Found', '');
?>
<div class="text-center py-5">
    <i class="bi bi-search text-paw" style="font-size: 4rem;"></i>
    <h1 class="mt-3 paw-page-title">404 — Page Not Found</h1>
    <p class="text-muted fs-5 mb-4">
        Hmm, we couldn't find that page. It may have moved or the URL might be incorrect.
    </p>
    <a href="<?= url('/') ?>" class="btn btn-paw">
        <i class="bi bi-house-fill"></i> Return Home
    </a>
</div>
<?php
layout_footer();
