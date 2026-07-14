<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_ADMIN);

$filters = [
    'status' => $_GET['status'] ?? '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = AdoptionModel::list_filtered($filters, $page);
$apps   = $result['rows'];

$statusOptions = [
    ''               => 'All Statuses',
    'submitted'      => 'Submitted',
    'under_review'   => 'Under Review',
    'approved'       => 'Approved',
    'rejected'       => 'Rejected',
    'withdrawn'      => 'Withdrawn',
    'completed'      => 'Completed',
];

layout_header('Adoption Applications', 'adoption');
admin_nav('adoption');
?>

<div class="container-fluid py-4">
    <h1 class="paw-page-title mb-3">Adoption Applications</h1>

    <!-- Filter -->
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
            <a href="<?= url('/admin/adoption/index.php') ?>" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Pet</th>
                    <th>Applicant</th>
                    <th>Status</th>
                    <th>Date Applied</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apps)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No applications found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($apps as $app): ?>
                <?php
                $statusColor = [
                    'submitted'    => 'badge-status--submitted',
                    'under_review' => 'badge-status--under_review',
                    'approved'     => 'badge-status--resolved',
                    'rejected'     => 'badge-status--rejected',
                    'completed'    => 'badge-status--verified',
                    'withdrawn'    => 'badge-status--archived',
                ][$app['status']] ?? 'badge-status--submitted';
                ?>
                <tr>
                    <td class="text-muted small">#<?= (int)$app['id'] ?></td>
                    <td class="fw-semibold"><?= e($app['pet_name']) ?></td>
                    <td><?= e($app['applicant_name']) ?></td>
                    <td>
                        <span class="badge <?= $statusColor ?>">
                            <?= e(ucwords(str_replace('_', ' ', $app['status']))) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e(date('M d, Y', strtotime($app['created_at']))) ?></td>
                    <td>
                        <a href="<?= url('/admin/adoption/view.php?id=' . (int)$app['id']) ?>"
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

    <!-- Pagination -->
    <?php
    $qStr = $filters['status'] ? 'status=' . urlencode($filters['status']) . '&' : '';
    echo render_pager($result, url('/admin/adoption/index.php?' . $qStr . 'page=%d'));
    ?>
</div>

<?php layout_footer(); ?>
