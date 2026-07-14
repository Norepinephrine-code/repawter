<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_ADMIN);

$filters = [
    'role'   => $_GET['role']   ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
];
$page = max(1, (int)($_GET['page'] ?? 1));

$result = UserModel::list_filtered($filters, $page);
$users  = $result['rows'];

$qBase    = http_build_query(array_merge(array_filter($filters), ['page' => '%d']));
$pagerUrl = '?' . $qBase;

$roles    = [ROLE_RESIDENT, ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN];
$statuses = ['active', 'flagged', 'suspended'];

$roleLabels = [
    ROLE_RESIDENT => 'Community Resident',
    ROLE_OFFICIAL => 'Barangay Official',
    ROLE_WELFARE  => 'Welfare Org',
    ROLE_ADMIN    => 'System Admin',
];

layout_header('User Management', 'admin');
admin_nav('users');
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="paw-page-title mb-0">User Management</h1>
        <span class="text-muted"><?= (int)$result['total'] ?> user(s) found</span>
    </div>

    <!-- Filter form -->
    <form method="get" class="row g-2 mb-4 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label small">Role</label>
            <select name="role" class="form-select form-select-sm">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?= e($r) ?>"<?= $filters['role'] === $r ? ' selected' : '' ?>><?= e($roleLabels[$r] ?? $r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-4">
            <label class="form-label small">Search (name or email)</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or email…" value="<?= e($filters['search']) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-paw btn-sm">Filter</button>
            <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Member Since</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                <?php else: ?>
                <?php foreach ($users as $u): ?>
                <?php
                    $statusClass = match($u['account_status']) {
                        'active'    => 'success',
                        'flagged'   => 'warning',
                        'suspended' => 'danger',
                        default     => 'secondary',
                    };
                ?>
                <tr>
                    <td class="text-muted small"><?= (int)$u['id'] ?></td>
                    <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                    <td class="text-muted small"><?= e($u['email']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($roleLabels[$u['role']] ?? $u['role']) ?></span></td>
                    <td><?= e($u['barangay_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $statusClass ?>"><?= e(ucfirst($u['account_status'])) ?></span></td>
                    <td><small><?= e(date('M d, Y', strtotime($u['created_at']))) ?></small></td>
                    <td>
                        <a href="<?= url('/admin/users/view.php?id=' . (int)$u['id']) ?>" class="btn btn-paw-teal btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= render_pager($result, '?' . http_build_query(array_merge(array_filter($filters), ['page' => '%d']))) ?>
</div>

<?php layout_footer(); ?>
