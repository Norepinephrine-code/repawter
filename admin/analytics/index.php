<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

// --- Report volume by month (last 12 months) ---
$monthlyRows = db_all(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
     FROM reports
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY ym
     ORDER BY ym ASC"
);

// --- Reports by status ---
$byStatus = db_all(
    "SELECT status, COUNT(*) AS cnt FROM reports GROUP BY status ORDER BY cnt DESC"
);

// --- Reports by barangay ---
$byBarangay = db_all(
    "SELECT b.name AS barangay_name, COUNT(r.id) AS cnt
     FROM reports r
     LEFT JOIN barangays b ON b.id = r.barangay_id
     GROUP BY r.barangay_id, b.name
     ORDER BY cnt DESC
     LIMIT 20"
);

// --- Reports by urgency ---
$byUrgency = db_all(
    "SELECT urgency, COUNT(*) AS cnt FROM reports GROUP BY urgency ORDER BY FIELD(urgency,'low','medium','high','critical')"
);

// --- Avg response times ---
$responseTimes = db_one(
    "SELECT
        ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)), 1) AS avg_verify_hours,
        ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)), 1) AS avg_resolve_hours,
        COUNT(CASE WHEN verified_at IS NOT NULL THEN 1 END) AS verified_count,
        COUNT(CASE WHEN resolved_at IS NOT NULL THEN 1 END) AS resolved_count
     FROM reports"
);

// --- Case outcomes ---
$caseOutcomes = db_all(
    "SELECT status, COUNT(*) AS cnt FROM cases GROUP BY status ORDER BY cnt DESC"
);

// --- Foster application counts by status ---
$fosterStats = db_all(
    "SELECT status, COUNT(*) AS cnt FROM foster_applications GROUP BY status ORDER BY cnt DESC"
);

// --- Adoption application counts by status ---
$adoptionStats = db_all(
    "SELECT status, COUNT(*) AS cnt FROM adoption_applications GROUP BY status ORDER BY cnt DESC"
);

// Prepare JS-safe data
$monthLabels    = json_encode(array_column($monthlyRows, 'ym'));
$monthData      = json_encode(array_map('intval', array_column($monthlyRows, 'cnt')));
$statusLabels   = json_encode(array_column($byStatus, 'status'));
$statusData     = json_encode(array_map('intval', array_column($byStatus, 'cnt')));
$urgencyLabels  = json_encode(array_column($byUrgency, 'urgency'));
$urgencyData    = json_encode(array_map('intval', array_column($byUrgency, 'cnt')));

layout_header('Analytics Dashboard', 'admin');
admin_nav('analytics');
?>

<div class="container-fluid py-4">
    <h1 class="paw-page-title mb-4">Analytics Dashboard</h1>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= (int)array_sum(array_column($byStatus, 'cnt')) ?></div>
                    <div class="small text-muted">Total Reports</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= (int)($responseTimes['verified_count'] ?? 0) ?></div>
                    <div class="small text-muted">Reports Verified</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= $responseTimes['avg_verify_hours'] ?? '—' ?></div>
                    <div class="small text-muted">Avg. Hours to Verify</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card paw-card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-paw"><?= $responseTimes['avg_resolve_hours'] ?? '—' ?></div>
                    <div class="small text-muted">Avg. Hours to Resolve</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Report Volume by Month -->
        <div class="col-lg-8">
            <div class="card paw-card h-100">
                <div class="card-header fw-semibold">Report Volume — Last 12 Months</div>
                <div class="card-body">
                    <canvas id="chartMonthly" height="120"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Month</th><th>Reports</th></tr></thead>
                            <tbody>
                            <?php foreach ($monthlyRows as $row): ?>
                                <tr><td><?= e($row['ym']) ?></td><td><?= (int)$row['cnt'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (empty($monthlyRows)): ?>
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports by Status -->
        <div class="col-lg-4">
            <div class="card paw-card h-100">
                <div class="card-header fw-semibold">Reports by Status</div>
                <div class="card-body">
                    <canvas id="chartStatus" height="160"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($byStatus as $row): ?>
                                <tr>
                                    <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($byStatus)): ?>
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports by Barangay -->
        <div class="col-lg-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Reports by Barangay (Top 20)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Barangay</th><th>Reports</th></tr></thead>
                            <tbody>
                            <?php foreach ($byBarangay as $row): ?>
                                <tr>
                                    <td><?= e($row['barangay_name'] ?? '(No Barangay)') ?></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($byBarangay)): ?>
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports by Urgency -->
        <div class="col-lg-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Reports by Urgency</div>
                <div class="card-body">
                    <canvas id="chartUrgency" height="130"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Urgency</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($byUrgency as $row): ?>
                                <tr>
                                    <td><span class="badge urgency--<?= e($row['urgency']) ?>"><?= e(ucfirst($row['urgency'])) ?></span></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($byUrgency)): ?>
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Case Outcomes -->
        <div class="col-lg-4">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Case Outcomes</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($caseOutcomes as $row): ?>
                                <tr>
                                    <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($caseOutcomes)): ?>
                                <tr><td colspan="2" class="text-muted">No cases yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Foster Application Stats -->
        <div class="col-lg-4">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Foster Applications by Status</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($fosterStats as $row): ?>
                                <tr>
                                    <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fosterStats)): ?>
                                <tr><td colspan="2" class="text-muted">No applications yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adoption Application Stats -->
        <div class="col-lg-4">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Adoption Applications by Status</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($adoptionStats as $row): ?>
                                <tr>
                                    <td><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                                    <td><?= (int)$row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($adoptionStats)): ?>
                                <tr><td colspan="2" class="text-muted">No applications yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Summary Link -->
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <strong>Monthly Summary Report</strong>
                        <p class="mb-0 text-muted small">Printable / exportable per-month breakdown.</p>
                    </div>
                    <a href="<?= url('/admin/analytics/monthly_summary.php') ?>" class="btn btn-paw">
                        <i class="bi bi-calendar3 me-1"></i> View Monthly Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php layout_footer([
    '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>',
    <<<JS
<script>
// Monthly chart
(function(){
    var ctx = document.getElementById('chartMonthly');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {$monthLabels},
            datasets: [{
                label: 'Reports',
                data: {$monthData},
                backgroundColor: 'rgba(231, 111, 81, 0.7)',
                borderColor: 'rgba(231, 111, 81, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
})();

// Status doughnut
(function(){
    var ctx = document.getElementById('chartStatus');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: {$statusLabels},
            datasets: [{ data: {$statusData}, backgroundColor: ['#6c757d','#0d6efd','#20c997','#dc3545','#6f42c1','#fd7e14','#198754','#343a40'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
})();

// Urgency bar
(function(){
    var ctx = document.getElementById('chartUrgency');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {$urgencyLabels},
            datasets: [{
                label: 'Reports',
                data: {$urgencyData},
                backgroundColor: ['#198754','#ffc107','#fd7e14','#dc3545']
            }]
        },
        options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
})();
</script>
JS
]); ?>
