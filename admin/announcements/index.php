<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$filters = [
    'status'   => $_GET['status']   ?? '',
    'category' => $_GET['category'] ?? '',
    'search'   => $_GET['search']   ?? '',
];
$page      = max(1, (int)($_GET['page'] ?? 1));
$paginated = AnnouncementModel::list_admin($filters, $page);
$rows      = $paginated['rows'];

$categoryColors = [
    'vaccination_drive'  => '#2A9D8F',
    'adoption_event'     => '#F4794E',
    'tnr_schedule'       => '#6C5CE7',
    'welfare_operation'  => '#0984E3',
    'general'            => '#636E72',
];

$categoryLabels = [
    'vaccination_drive'  => 'Vaccination Drive',
    'adoption_event'     => 'Adoption Event',
    'tnr_schedule'       => 'TNR Schedule',
    'welfare_operation'  => 'Welfare Operation',
    'general'            => 'General',
];

$statusLabels = [
    'draft'     => 'Draft',
    'scheduled' => 'Scheduled',
    'published' => 'Published',
    'archived'  => 'Archived',
];

$statusBadges = [
    'draft'     => 'secondary',
    'scheduled' => 'info text-dark',
    'published' => 'success',
    'archived'  => 'dark',
];

layout_header('Manage Announcements', 'announcements');
admin_nav('announcements');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="paw-page-title h3 mb-0">Announcements</h1>
    <a href="<?= url('/admin/announcements/edit.php') ?>" class="btn btn-paw">
        <i class="bi bi-plus-lg me-1"></i> New Announcement
    </a>
</div>

<!-- Filters -->
<form method="get" class="row g-2 mb-4 align-items-end">
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <?php foreach ($statusLabels as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>>
                    <?= e($lbl) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Category</label>
        <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <?php foreach ($categoryLabels as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filters['category'] === $val ? 'selected' : '' ?>>
                    <?= e($lbl) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Search Title</label>
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Search title..."
               value="<?= e($filters['search']) ?>">
    </div>
    <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-paw-teal btn-sm flex-fill">
            <i class="bi bi-search"></i> Filter
        </button>
        <a href="<?= url('/admin/announcements/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i>
        </a>
    </div>
</form>

<!-- Table -->
<?php if (empty($rows)): ?>
    <div class="alert alert-info">No announcements found matching the selected filters.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Event Date</th>
                <th>Author</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $ann): ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?= e($ann['title']) ?></div>
                    <?php if ($ann['barangay_name']): ?>
                        <div class="small text-muted">
                            <i class="bi bi-pin-map"></i> <?= e($ann['barangay_name']) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge"
                          style="background-color:<?= e($categoryColors[$ann['category']] ?? '#636E72') ?>">
                        <?= e($categoryLabels[$ann['category']] ?? $ann['category']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= e($statusBadges[$ann['status']] ?? 'secondary') ?>">
                        <?= e($statusLabels[$ann['status']] ?? $ann['status']) ?>
                    </span>
                </td>
                <td class="small">
                    <?php if ($ann['event_start']): ?>
                        <?= e(date('M j, Y', strtotime($ann['event_start']))) ?>
                        <?php if (!$ann['is_all_day']): ?>
                            <br><span class="text-muted"><?= e(date('g:i A', strtotime($ann['event_start']))) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= e($ann['author_name']) ?></td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <!-- Edit -->
                        <a href="<?= url('/admin/announcements/edit.php?id=' . $ann['id']) ?>"
                           class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <!-- Publish (only for draft/scheduled) -->
                        <?php if (in_array($ann['status'], ['draft', 'scheduled'], true)): ?>
                            <form method="post"
                                  action="<?= url('/actions/announcements/announcement_save.php') ?>"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id"     value="<?= (int)$ann['id'] ?>">
                                <input type="hidden" name="_quick_publish" value="1">
                                <button type="submit" class="btn btn-sm btn-paw-teal" title="Publish now">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <!-- View (if published) -->
                        <?php if ($ann['status'] === 'published'): ?>
                            <a href="<?= url('/announcements/view.php?id=' . $ann['id']) ?>"
                               target="_blank" class="btn btn-sm btn-outline-primary" title="View public">
                                <i class="bi bi-eye"></i>
                            </a>
                        <?php endif; ?>
                        <!-- Archive (soft delete) -->
                        <?php if ($ann['status'] !== 'archived'): ?>
                            <form method="post"
                                  action="<?= url('/actions/announcements/announcement_delete.php') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Archive this announcement?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$qs = http_build_query(array_filter($filters));
$qs = $qs ? '&' . $qs : '';
echo render_pager($paginated, url('/admin/announcements/index.php?page=%d' . $qs));
?>

<?php endif; ?>

<?php layout_footer(); ?>
