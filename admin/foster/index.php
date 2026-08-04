<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_OFFICIAL, ROLE_ADMIN);

$filters = [
    'status' => $_GET['status'] ?? '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = FosterModel::list_filtered($filters, $page);
$apps   = $result['rows'];

$statusOptions = [
    ''             => 'All Statuses',
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'approved'     => 'Approved',
    'rejected'     => 'Rejected',
    'withdrawn'    => 'Withdrawn',
];

$statusBadge = [
    'submitted'    => 'badge-status--submitted',
    'under_review' => 'badge-status--under_review',
    'approved'     => 'badge-status--resolved',
    'rejected'     => 'badge-status--rejected',
    'withdrawn'    => 'badge-status--archived',
];

$typeLabel = [
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    'other' => 'Other',
];

layout_header('Foster Applications', 'foster');
admin_nav('foster');
?>

<div class="container-fluid py-4">
    <h1 class="paw-page-title mb-3">Foster Applications</h1>

    <form method="get" class="row g-2 mb-4">
        <div class="col-auto">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($statusOptions as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>>
                    <?= e($lbl) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-secondary">Filter</button>
            <a href="<?= url('/admin/foster/index.php') ?>" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>
                    <th>Applicant</th>
                    <th>Preferred Type</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apps)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No applications found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($apps as $app): ?>
                <?php
                    $badgeClass = $statusBadge[$app['status']] ?? 'badge-status--submitted';
                    $statusText = ucwords(str_replace('_', ' ', $app['status']));
                    $type       = $typeLabel[$app['preferred_animal_type']] ?? ucfirst($app['preferred_animal_type']);
                ?>
                <tr>
                    <td class="text-muted small">
                    <td class="fw-semibold"><?= e($app['applicant_name']) ?></td>
                    <td><?= e($type) ?></td>
                    <td>
                        <span class="<?= (int)$app['current_foster_count'] >= (int)$app['household_capacity'] ? 'text-danger fw-semibold' : '' ?>">
                            <?= (int)$app['current_foster_count'] ?> / <?= (int)$app['household_capacity'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= e($badgeClass) ?>">
                            <?= e($statusText) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e(date('M d, Y', strtotime($app['created_at']))) ?></td>
                    <td>
                        <a href="<?= url('/admin/foster/view.php?id=' . (int)$app['id']) ?>"
                           class="btn btn-sm btn-paw-teal">
                            <i class="bi bi-eye"></i> Review
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $qStr = $filters['status'] ? 'status=' . urlencode($filters['status']) . '&' : '';
    echo render_pager($result, url('/admin/foster/index.php?' . $qStr . 'page=%d'));
    ?>
</div>

<?php layout_footer(); ?>
