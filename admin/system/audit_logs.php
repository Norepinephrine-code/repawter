<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_ADMIN);

$filterAction = trim($_GET['action'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$params = [];
$where  = '';
if ($filterAction !== '') {
    $where    = 'WHERE al.action = ?';
    $params[] = $filterAction;
}

$sql = "SELECT
            al.*,
            actor.full_name  AS actor_name,
            target.full_name AS target_name
        FROM audit_logs al
        LEFT JOIN users actor  ON actor.id  = al.actor_id
        LEFT JOIN users target ON target.id = al.target_user_id
        {$where}
        ORDER BY al.created_at DESC";

$result = paginate($sql, $params, $page);
$logs   = $result['rows'];

// Distinct action values for filter dropdown
$actionValues = db_all('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');

$qBase    = http_build_query(array_filter(['action' => $filterAction, 'page' => '%d']));
$pagerUrl = '?' . $qBase;

layout_header('Audit Logs', 'admin');
admin_nav('audit');
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="paw-page-title mb-0">Audit Logs</h1>
        <span class="text-muted"><?= (int)$result['total'] ?> log entries</span>
    </div>

    <!-- Filter -->
    <form method="get" class="row g-2 mb-4 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label small">Filter by Action</label>
            <select name="action" class="form-select form-select-sm">
                <option value="">All Actions</option>
                <?php foreach ($actionValues as $av): ?>
                <option value="<?= e($av['action']) ?>"<?= $filterAction === $av['action'] ? ' selected' : '' ?>><?= e($av['action']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-paw btn-sm">Filter</button>
            <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:160px">Time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Target User</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No audit log entries found.</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-muted"><?= e(date('M d, Y H:i:s', strtotime($log['created_at']))) ?></td>
                    <td class="small"><?= e($log['actor_name'] ?? ('ID ' . ($log['actor_id'] ?? '—'))) ?></td>
                    <td><code class="small"><?= e($log['action']) ?></code></td>
                    <td class="small text-muted">
                        <?php if ($log['entity_type']): ?>
                            <?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int)$log['entity_id'] : '' ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= $log['target_name'] ? e($log['target_name']) : '—' ?></td>
                    <td class="small"><?= e($log['description'] ?? '') ?>
                        <?php if (!empty($log['metadata_json'])): ?>
                        <?php $meta = json_decode($log['metadata_json'], true); ?>
                        <?php if (is_array($meta) && !empty($meta)): ?>
                        <br><span class="text-muted">
                        <?php foreach ($meta as $k => $v): ?>
                            <span class="badge bg-light text-dark border me-1"><?= e($k) ?>: <?= e((string)$v) ?></span>
                        <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= render_pager($result, '?' . http_build_query(array_filter(['action' => $filterAction, 'page' => '%d']))) ?>
</div>

<?php layout_footer(); ?>
