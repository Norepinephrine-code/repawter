<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$filters = [
    'status'      => $_GET['status']      ?? '',
    'urgency'     => $_GET['urgency']     ?? '',
    'barangay_id' => $_GET['barangay_id'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '',
    'search'      => $_GET['search']      ?? '',
];
$page = max(1, (int)($_GET['page'] ?? 1));

$result    = ReportModel::list_filtered($filters, $page);
$reports   = $result['rows'];
$barangays = db_all('SELECT id, name FROM barangays WHERE is_active = 1 ORDER BY name');
$staffList = db_all("SELECT id, full_name FROM users WHERE role IN ('barangay_official','welfare_org','system_admin') AND account_status = 'active' ORDER BY full_name");

$qBase = http_build_query(array_filter(array_merge($filters, ['page' => '%d'])));
$pagerUrl = '?' . $qBase;

layout_header('Report Queue', 'reports');
admin_nav('reports');
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="paw-page-title mb-0">Report Queue</h1>
        <span class="text-muted"><?= (int)$result['total'] ?> report(s) found</span>
    </div>

    <form method="get" class="row g-2 mb-4 align-items-end">
        <div class="col-sm-6 col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach (['submitted','under_review','verified','rejected','assigned','in_progress','resolved','archived'] as $s): ?>
                <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small">Urgency</label>
            <select name="urgency" class="form-select form-select-sm">
                <option value="">All Urgencies</option>
                <?php foreach (['low','medium','high','critical'] as $u): ?>
                <option value="<?= e($u) ?>"<?= $filters['urgency'] === $u ? ' selected' : '' ?>><?= e(ucfirst($u)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small">Barangay</label>
            <select name="barangay_id" class="form-select form-select-sm">
                <option value="">All Barangays</option>
                <?php foreach ($barangays as $b): ?>
                <option value="<?= e($b['id']) ?>"<?= (string)$filters['barangay_id'] === (string)$b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small">Assigned To</label>
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">Anyone</option>
                <?php foreach ($staffList as $s): ?>
                <option value="<?= e($s['id']) ?>"<?= (string)$filters['assigned_to'] === (string)$s['id'] ? ' selected' : '' ?>><?= e($s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Title, description, address…" value="<?= e($filters['search']) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-paw btn-sm">Filter</button>
            <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>
                    <th>Photo</th>
                    <th>Title / Animal</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Barangay</th>
                    <th>Reporter</th>
                    <th>Submitted</th>
                    <th>Assigned To</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No reports found.</td></tr>
                <?php else: ?>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="text-muted small"><?= e($r['id']) ?></td>
                    <td>
                        <?php if (!empty($r['photo_path'])): ?>
                        <img src="<?= e(upload_url($r['photo_path'])) ?>"
                             alt="Report photo"
                             style="width:56px;height:44px;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                        <div style="width:56px;height:44px;background:#eee;border-radius:4px;"></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($r['title'] ?: '(Untitled)') ?></div>
                        <small class="text-muted"><?= e(ucfirst($r['animal_type'])) ?><?= !empty($r['species_other']) ? ' — ' . e($r['species_other']) : '' ?></small>
                    </td>
                    <td><span class="badge urgency--<?= e($r['urgency']) ?>"><?= e(ucfirst($r['urgency'])) ?></span></td>
                    <td><?php partial('report_status_badge', ['status' => $r['status']]) ?></td>
                    <td><?= e($r['barangay_name'] ?? '—') ?></td>
                    <td><?= e($r['reporter_name'] ?? '—') ?></td>
                    <td><small><?= e(date('M d, Y', strtotime($r['created_at']))) ?></small></td>
                    <td><?= e($r['assigned_to_name'] ?? '—') ?></td>
                    <td>
                        <a href="<?= url('/admin/reports/view.php?id=' . (int)$r['id']) ?>" class="btn btn-paw-teal btn-sm">Manage</a>
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
