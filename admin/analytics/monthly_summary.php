<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$month = trim($_GET['month'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
$export = isset($_GET['export']) && $_GET['export'] === 'csv';

$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

$totalReports = db_one(
    "SELECT COUNT(*) AS cnt FROM reports WHERE DATE_FORMAT(created_at,'%Y-%m') = ?",
    [$month]
);

$byStatus = db_all(
    "SELECT status, COUNT(*) AS cnt FROM reports WHERE DATE_FORMAT(created_at,'%Y-%m') = ? GROUP BY status ORDER BY cnt DESC",
    [$month]
);

$byUrgency = db_all(
    "SELECT urgency, COUNT(*) AS cnt FROM reports WHERE DATE_FORMAT(created_at,'%Y-%m') = ? GROUP BY urgency ORDER BY FIELD(urgency,'low','medium','high','critical')",
    [$month]
);

$byBarangay = db_all(
    "SELECT b.name AS barangay_name, COUNT(r.id) AS cnt
     FROM reports r
     LEFT JOIN barangays b ON b.id = r.barangay_id
     WHERE DATE_FORMAT(r.created_at,'%Y-%m') = ?
     GROUP BY r.barangay_id, b.name
     ORDER BY cnt DESC",
    [$month]
);

$resolvedStats = db_one(
    "SELECT
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) AS resolved_count,
        ROUND(AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END), 1) AS avg_resolve_hours
     FROM reports
     WHERE DATE_FORMAT(created_at,'%Y-%m') = ?",
    [$month]
);

$fosterApps = db_all(
    "SELECT status, COUNT(*) AS cnt FROM foster_applications WHERE DATE_FORMAT(created_at,'%Y-%m') = ? GROUP BY status ORDER BY cnt DESC",
    [$month]
);

$adoptionApps = db_all(
    "SELECT status, COUNT(*) AS cnt FROM adoption_applications WHERE DATE_FORMAT(created_at,'%Y-%m') = ? GROUP BY status ORDER BY cnt DESC",
    [$month]
);

$casesOpened = db_one(
    "SELECT COUNT(*) AS cnt FROM cases WHERE DATE_FORMAT(opened_at,'%Y-%m') = ?",
    [$month]
);

$casesClosed = db_one(
    "SELECT COUNT(*) AS cnt FROM cases WHERE closed_at IS NOT NULL AND DATE_FORMAT(closed_at,'%Y-%m') = ?",
    [$month]
);

if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="repawter-summary-' . $month . '.csv"');
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    fputcsv($out, ['RePawter Monthly Summary', $month]);
    fputcsv($out, []);

    fputcsv($out, ['TOTAL REPORTS', (int)($totalReports['cnt'] ?? 0)]);
    fputcsv($out, ['RESOLVED COUNT', (int)($resolvedStats['resolved_count'] ?? 0)]);
    fputcsv($out, ['AVG RESOLUTION TIME (hours)', $resolvedStats['avg_resolve_hours'] ?? 'N/A']);
    fputcsv($out, ['CASES OPENED', (int)($casesOpened['cnt'] ?? 0)]);
    fputcsv($out, ['CASES CLOSED', (int)($casesClosed['cnt'] ?? 0)]);
    fputcsv($out, []);

    fputcsv($out, ['REPORTS BY STATUS']);
    fputcsv($out, ['Status', 'Count']);
    foreach ($byStatus as $row) {
        fputcsv($out, [ucfirst(str_replace('_', ' ', $row['status'])), (int)$row['cnt']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['REPORTS BY URGENCY']);
    fputcsv($out, ['Urgency', 'Count']);
    foreach ($byUrgency as $row) {
        fputcsv($out, [ucfirst($row['urgency']), (int)$row['cnt']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['REPORTS BY BARANGAY']);
    fputcsv($out, ['Barangay', 'Count']);
    foreach ($byBarangay as $row) {
        fputcsv($out, [$row['barangay_name'] ?? '(No Barangay)', (int)$row['cnt']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['FOSTER APPLICATIONS BY STATUS']);
    fputcsv($out, ['Status', 'Count']);
    foreach ($fosterApps as $row) {
        fputcsv($out, [ucfirst(str_replace('_', ' ', $row['status'])), (int)$row['cnt']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['ADOPTION APPLICATIONS BY STATUS']);
    fputcsv($out, ['Status', 'Count']);
    foreach ($adoptionApps as $row) {
        fputcsv($out, [ucfirst(str_replace('_', ' ', $row['status'])), (int)$row['cnt']]);
    }

    fclose($out);
    exit;
}

layout_header('Monthly Summary — ' . $month, 'admin');
admin_nav('analytics');
?>

<style>
@media print {
    .paw-admin-subnav, .paw-navbar, footer, .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
    .card { border: 1px solid
}
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="paw-page-title mb-1">Monthly Summary</h1>
            <p class="text-muted mb-0">Data for <strong><?= e($month) ?></strong></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="get" class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 small fw-semibold">Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>">
                <button type="submit" class="btn btn-paw btn-sm">Go</button>
            </form>
            <a href="?month=<?= urlencode($month) ?>&export=csv" class="btn btn-paw-outline btn-sm">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="text-center mb-4 d-none d-print-block">
        <h2>RePawter — Monthly Summary Report</h2>
        <h4><?= e($month) ?></h4>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= (int)($totalReports['cnt'] ?? 0) ?></div>
                    <div class="small text-muted">Total Reports</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= (int)($resolvedStats['resolved_count'] ?? 0) ?></div>
                    <div class="small text-muted">Resolved</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= $resolvedStats['avg_resolve_hours'] ?? '—' ?></div>
                    <div class="small text-muted">Avg. Hours to Resolve</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= (int)($casesOpened['cnt'] ?? 0) ?> / <?= (int)($casesClosed['cnt'] ?? 0) ?></div>
                    <div class="small text-muted">Cases Opened / Closed</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Reports by Status</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($byStatus as $row): ?>
                            <tr>
                                <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                <td class="text-end"><?= (int)$row['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byStatus)): ?>
                            <tr><td colspan="2" class="text-muted text-center p-3">No reports this month.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Reports by Urgency</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Urgency</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($byUrgency as $row): ?>
                            <tr>
                                <td><span class="badge urgency--<?= e($row['urgency']) ?>"><?= e(ucfirst($row['urgency'])) ?></span></td>
                                <td class="text-end"><?= (int)$row['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byUrgency)): ?>
                            <tr><td colspan="2" class="text-muted text-center p-3">No data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Reports by Barangay</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Barangay</th><th class="text-end">Reports</th></tr></thead>
                        <tbody>
                        <?php foreach ($byBarangay as $row): ?>
                            <tr>
                                <td><?= e($row['barangay_name'] ?? '(No Barangay)') ?></td>
                                <td class="text-end"><?= (int)$row['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byBarangay)): ?>
                            <tr><td colspan="2" class="text-muted text-center p-3">No data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">New Foster Applications</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($fosterApps as $row): ?>
                            <tr>
                                <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                <td class="text-end"><?= (int)$row['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fosterApps)): ?>
                            <tr><td colspan="2" class="text-muted text-center p-3">None this month.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">New Adoption Applications</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($adoptionApps as $row): ?>
                            <tr>
                                <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                <td class="text-end"><?= (int)$row['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($adoptionApps)): ?>
                            <tr><td colspan="2" class="text-muted text-center p-3">None this month.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
