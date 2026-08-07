<?php
require_once __DIR__ . '/../app/bootstrap.php';

$page       = max(1, (int)($_GET['page']     ?? 1));
$categoryId = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int)$_GET['cat'] : null;

$categories = ResourceModel::categories();
$result     = ResourceModel::list_published($categoryId, $page);
$resources  = $result['rows'];

layout_header('Educational Resources', 'resources');
?>

<div class="paw-hero text-center mb-5" style="padding: 3rem 1rem;">
    <h1 class="display-5 fw-bold mb-2"><i class="bi bi-journal-richtext me-2"></i>Educational Resources</h1>
    <p class="lead mb-0">Learn about responsible pet ownership, animal welfare, and community care.</p>
</div>

<?php if (!empty($categories)): ?>
<div class="mb-4 d-flex flex-wrap gap-2 justify-content-center">
    <a href="<?= url('/resources/') ?>"
       class="btn btn-sm <?= $categoryId === null ? 'btn-paw' : 'btn-paw-outline' ?>">
        All
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= url('/resources/?cat=' . (int)$cat['id']) ?>"
       class="btn btn-sm <?= $categoryId === (int)$cat['id'] ? 'btn-paw' : 'btn-paw-outline' ?>">
        <?= e($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($resources)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
    <p>No resources found<?= $categoryId !== null ? ' in this category' : '' ?>.</p>
</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
    <?php foreach ($resources as $res): ?>
    <div class="col">
        <div class="paw-card card h-100 shadow-sm">
            <?php if (!empty($res['cover_image_path'])): ?>
            <img src="<?= e(photo_url($res['cover_image_path'] ?? null)) ?>"
                 alt="<?= e($res['title']) ?>"
                 class="card-img-top"
                 style="height:180px;object-fit:cover;">
            <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                 style="height:180px;">
                <i class="bi bi-journal-richtext fs-1 text-muted"></i>
            </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <?php if (!empty($res['category_name'])): ?>
                <span class="badge bg-info text-dark mb-2 align-self-start">
                    <?= e($res['category_name']) ?>
                </span>
                <?php endif; ?>
                <h5 class="card-title"><?= e($res['title']) ?></h5>
                <?php if (!empty($res['summary'])): ?>
                <p class="card-text text-muted small flex-grow-1"><?= e($res['summary']) ?></p>
                <?php endif; ?>
                <a href="<?= url('/resources/view.php?id=' . (int)$res['id']) ?>"
                   class="btn btn-paw btn-sm mt-3 align-self-start">
                    <i class="bi bi-book me-1"></i>Read
                </a>
            </div>
            <?php if (!empty($res['published_at'])): ?>
            <div class="card-footer text-muted small">
                <i class="bi bi-calendar3 me-1"></i><?= e(date('M j, Y', strtotime($res['published_at']))) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
$pattern = url('/resources/?') . ($categoryId !== null ? 'cat=' . $categoryId . '&' : '') . 'page=%d';
$pagerHtml = render_pager($result, $pattern);
if ($pagerHtml !== '') echo $pagerHtml;
?>
<?php endif; ?>
<?php
layout_footer();
