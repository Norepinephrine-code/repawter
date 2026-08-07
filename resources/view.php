<?php
require_once __DIR__ . '/../app/bootstrap.php';

$id  = (int)($_GET['id'] ?? 0);
$res = $id > 0 ? ResourceModel::find($id) : null;

if ($res === null || !$res['is_published']) {
    http_response_code(404);
    layout_header('Not Found', 'resources');
    echo '<div class="text-center py-5"><h2>Resource not found.</h2>'
       . '<a href="' . url('/resources/') . '" class="btn btn-paw mt-3">Back to Resources</a></div>';
    layout_footer();
    exit;
}

layout_header(e($res['title']), 'resources');
?>
<article class="col-lg-8 mx-auto">

    <a href="<?= url('/resources/') ?>" class="text-decoration-none text-muted mb-3 d-inline-block">
        <i class="bi bi-arrow-left me-1"></i>Back to Resources
    </a>

    <?php if (!empty($res['cover_image_path'])): ?>
    <img src="<?= e(photo_url($res['cover_image_path'] ?? null)) ?>"
         alt="<?= e($res['title']) ?>"
         class="img-fluid rounded mb-4 w-100"
         style="max-height:380px;object-fit:cover;">
    <?php endif; ?>

    <?php if (!empty($res['category_name'])): ?>
    <span class="badge bg-info text-dark mb-3"><?= e($res['category_name']) ?></span>
    <?php endif; ?>

    <h1 class="paw-page-title"><?= e($res['title']) ?></h1>

    <?php if (!empty($res['summary'])): ?>
    <p class="lead text-muted mb-4"><?= e($res['summary']) ?></p>
    <?php endif; ?>

    <?php if (!empty($res['published_at'])): ?>
    <p class="text-muted small mb-4">
        <i class="bi bi-calendar3 me-1"></i>Published <?= e(date('F j, Y', strtotime($res['published_at']))) ?>
    </p>
    <?php endif; ?>

    <hr class="mb-4">

    <div class="resource-body lh-lg">
        <?= $res['body'] ?>
    </div>

    <hr class="mt-5 mb-4">
    <a href="<?= url('/resources/') ?>" class="btn btn-paw-outline">
        <i class="bi bi-arrow-left me-1"></i>Back to Resources
    </a>
</article>
<?php
layout_footer();
