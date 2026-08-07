<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_ADMIN, ROLE_OFFICIAL, ROLE_WELFARE);

$page   = max(1, (int)($_GET['page'] ?? 1));
$result = ResourceModel::list_admin($page);
$rows   = $result['rows'];

layout_header('Manage Resources', 'resources');
admin_nav('resources');
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="paw-page-title mb-0">
        <i class="bi bi-journal-richtext me-2"></i>Educational Resources
    </h1>
    <a href="<?= url('/admin/resources/edit.php') ?>" class="btn btn-paw">
        <i class="bi bi-plus-lg me-1"></i>New Resource
    </a>
</div>

<?php if (empty($rows)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
    <p>No resources yet. <a href="<?= url('/admin/resources/edit.php') ?>">Create one</a>.</p>
</div>
<?php else: ?>
<div class="table-responsive shadow-sm rounded">
    <table class="table table-hover align-middle mb-0 bg-white">
        <thead class="table-light">
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Published</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $res): ?>
        <tr>
            <td>
                <strong><?= e($res['title']) ?></strong>
                <div class="text-muted small"><?= e($res['slug']) ?></div>
            </td>
            <td>
                <?php if (!empty($res['category_name'])): ?>
                <span class="badge bg-info text-dark"><?= e($res['category_name']) ?></span>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($res['is_published']): ?>
                <span class="badge bg-success"><i class="bi bi-eye me-1"></i>Published</span>
                <?php else: ?>
                <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Draft</span>
                <?php endif; ?>
            </td>
            <td class="text-muted small">
                <?= !empty($res['published_at']) ? e(date('M j, Y', strtotime($res['published_at']))) : '—' ?>
            </td>
            <td class="text-end">
                <a href="<?= url('/admin/resources/edit.php?id=' . (int)$res['id']) ?>"
                   class="btn btn-sm btn-paw-outline me-1">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <?php if ($res['is_published']): ?>
                <form method="post" action="<?= url('/actions/resources/resource_delete.php') ?>"
                      class="d-inline"
                      data-confirm="Unpublish this resource?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$res['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-eye-slash me-1"></i>Unpublish
                    </button>
                </form>
                <?php else: ?>
                <span class="text-muted small">Draft</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$pagerHtml = render_pager($result, url('/admin/resources/index.php?page=%d'));
if ($pagerHtml !== '') echo '<div class="mt-4">' . $pagerHtml . '</div>';
?>
<?php endif; ?>
<?php
layout_footer();
