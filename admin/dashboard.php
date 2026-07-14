<?php
require __DIR__ . '/../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

// ── Reports aggregate ──────────────────────────────────────────────────────
$reportTotal  = (int)(db_one('SELECT COUNT(*) AS c FROM reports')['c'] ?? 0);

$statusCounts = [];
foreach (db_all(
    "SELECT status, COUNT(*) AS c FROM reports GROUP BY status"
) as $row) {
    $statusCounts[$row['status']] = (int)$row['c'];
}

$urgentOpen = (int)(db_one(
    "SELECT COUNT(*) AS c FROM reports
     WHERE urgency IN ('high','critical')
       AND status NOT IN ('resolved','archived','rejected')"
)['c'] ?? 0);

$needsAttention = ($statusCounts['submitted'] ?? 0) + ($statusCounts['under_review'] ?? 0);

// ── Applications ──────────────────────────────────────────────────────────
$pendingFoster = (int)(db_one(
    "SELECT COUNT(*) AS c FROM foster_applications
     WHERE status IN ('submitted','under_review')"
)['c'] ?? 0);

$pendingAdoption = (int)(db_one(
    "SELECT COUNT(*) AS c FROM adoption_applications
     WHERE status IN ('submitted','under_review')"
)['c'] ?? 0);

// ── Pets & Cases ──────────────────────────────────────────────────────────
$petsAvailable = (int)(db_one(
    "SELECT COUNT(*) AS c FROM pets WHERE status = 'available'"
)['c'] ?? 0);

$openCases = (int)(db_one(
    "SELECT COUNT(*) AS c FROM cases
     WHERE status NOT IN ('closed','adopted','released','deceased')"
)['c'] ?? 0);

// ── Users by role (admin only) ────────────────────────────────────────────
$usersByRole = [];
if (can('manage_users')) {
    foreach (db_all('SELECT role, COUNT(*) AS c FROM users GROUP BY role') as $row) {
        $usersByRole[$row['role']] = (int)$row['c'];
    }
}

// ── Recent reports (latest 5) ─────────────────────────────────────────────
$recentReports = db_all(
    "SELECT r.id, r.title, r.status, r.urgency, r.created_at,
            u.full_name AS reporter_name
     FROM reports r
     LEFT JOIN users u ON u.id = r.reporter_id
     ORDER BY r.created_at DESC
     LIMIT 5"
);

layout_header('Staff Dashboard', 'admin');
admin_nav('dashboard');

// Helper: friendly status label
function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="paw-page-title mb-0">
        <i class="bi bi-speedometer2 me-2"></i>Staff Dashboard
    </h1>
    <span class="text-muted small">
        <?= e(date('l, F j, Y')) ?>
    </span>
</div>

<!-- ── Overview cards ──────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Reports -->
    <div class="col-sm-6 col-xl-3">
        <div class="paw-card card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Total Reports</div>
                        <div class="display-6 fw-bold text-paw-dark"><?= $reportTotal ?></div>
                    </div>
                    <span class="fs-2 text-warning"><i class="bi bi-flag-fill"></i></span>
                </div>
                <?php if ($needsAttention > 0): ?>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark">
                        <?= $needsAttention ?> awaiting review
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Urgent Open Reports -->
    <div class="col-sm-6 col-xl-3">
        <div class="paw-card card h-100 shadow-sm <?= $urgentOpen > 0 ? 'border-danger' : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Urgent / Critical</div>
                        <div class="display-6 fw-bold <?= $urgentOpen > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $urgentOpen ?>
                        </div>
                    </div>
                    <span class="fs-2 <?= $urgentOpen > 0 ? 'text-danger' : 'text-success' ?>">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                </div>
                <div class="mt-2 text-muted small">open reports needing attention</div>
            </div>
        </div>
    </div>

    <!-- Pets Available -->
    <div class="col-sm-6 col-xl-3">
        <div class="paw-card card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Pets Available</div>
                        <div class="display-6 fw-bold text-paw-dark"><?= $petsAvailable ?></div>
                    </div>
                    <span class="fs-2 text-danger"><i class="bi bi-heart-fill"></i></span>
                </div>
                <div class="mt-2 text-muted small">listed for adoption</div>
            </div>
        </div>
    </div>

    <!-- Open Cases -->
    <div class="col-sm-6 col-xl-3">
        <div class="paw-card card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Open Cases</div>
                        <div class="display-6 fw-bold text-paw-dark"><?= $openCases ?></div>
                    </div>
                    <span class="fs-2 text-info"><i class="bi bi-clipboard2-pulse"></i></span>
                </div>
                <div class="mt-2 text-muted small">active rescue cases</div>
            </div>
        </div>
    </div>

</div>

<!-- ── Applications + Report status breakdown ─────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Pending Applications -->
    <div class="col-lg-4">
        <div class="paw-card card h-100 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Applications
            </div>
            <div class="card-body">
                <?php if (can('review_foster')): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span><i class="bi bi-house-heart me-2 text-success"></i>Foster</span>
                    <span class="badge bg-<?= $pendingFoster > 0 ? 'warning text-dark' : 'secondary' ?> fs-6">
                        <?= $pendingFoster ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (can('review_adoption')): ?>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-bag-heart me-2 text-paw"></i>Adoption</span>
                    <span class="badge bg-<?= $pendingAdoption > 0 ? 'warning text-dark' : 'secondary' ?> fs-6">
                        <?= $pendingAdoption ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!can('review_foster') && !can('review_adoption')): ?>
                <p class="text-muted small mb-0">No application permissions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Report Status Breakdown -->
    <?php if (can('manage_reports')): ?>
    <div class="col-lg-8">
        <div class="paw-card card h-100 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-pie-chart-fill me-2 text-warning"></i>Report Status Breakdown
            </div>
            <div class="card-body">
                <div class="row g-2">
                <?php
                $statusDefs = [
                    'submitted'    => ['label' => 'Submitted',    'class' => 'badge-status--submitted'],
                    'under_review' => ['label' => 'Under Review', 'class' => 'badge-status--under_review'],
                    'verified'     => ['label' => 'Verified',     'class' => 'badge-status--verified'],
                    'assigned'     => ['label' => 'Assigned',     'class' => 'badge-status--assigned'],
                    'in_progress'  => ['label' => 'In Progress',  'class' => 'badge-status--in_progress'],
                    'resolved'     => ['label' => 'Resolved',     'class' => 'badge-status--resolved'],
                    'rejected'     => ['label' => 'Rejected',     'class' => 'badge-status--rejected'],
                    'archived'     => ['label' => 'Archived',     'class' => 'badge-status--archived'],
                ];
                foreach ($statusDefs as $sKey => $sDef):
                    $cnt = $statusCounts[$sKey] ?? 0;
                ?>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <span class="<?= e($sDef['class']) ?>"><?= e($sDef['label']) ?></span>
                        <div class="fw-bold mt-1"><?= $cnt ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if (can('manage_users') && !empty($usersByRole)): ?>
<!-- ── Users by Role (admin) ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="paw-card card shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-people-fill me-2 text-primary"></i>Users by Role
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php
                    $roleLabels = [
                        ROLE_RESIDENT => 'Community Resident',
                        ROLE_OFFICIAL => 'Barangay Official',
                        ROLE_WELFARE  => 'Welfare Org',
                        ROLE_ADMIN    => 'System Admin',
                    ];
                    foreach ($roleLabels as $rKey => $rLabel):
                        $cnt = $usersByRole[$rKey] ?? 0;
                    ?>
                    <tr>
                        <td class="px-3"><?= e($rLabel) ?></td>
                        <td class="text-end px-3 fw-bold"><?= $cnt ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td class="px-3">Total</td>
                        <td class="text-end px-3"><?= array_sum($usersByRole) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Quick Actions ──────────────────────────────────────────────────── -->
<div class="mb-4">
    <h5 class="fw-semibold mb-3"><i class="bi bi-lightning-fill me-2 text-warning"></i>Quick Actions</h5>
    <div class="d-flex flex-wrap gap-2">
        <?php if (can('manage_reports')): ?>
        <a href="<?= url('/admin/reports/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-flag-fill me-1"></i>Reports Queue
        </a>
        <?php endif; ?>
        <?php if (can('track_cases')): ?>
        <a href="<?= url('/admin/reports/case_tracking.php') ?>" class="btn btn-paw-outline">
            <i class="bi bi-clipboard2-pulse me-1"></i>Case Tracking
        </a>
        <?php endif; ?>
        <?php if (can('review_foster')): ?>
        <a href="<?= url('/admin/foster/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-house-heart me-1"></i>Foster Applications
        </a>
        <?php endif; ?>
        <?php if (can('manage_pets')): ?>
        <a href="<?= url('/admin/pets/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-heart-fill me-1"></i>Manage Pets
        </a>
        <?php endif; ?>
        <?php if (can('review_adoption')): ?>
        <a href="<?= url('/admin/adoption/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-bag-heart me-1"></i>Adoption Applications
        </a>
        <?php endif; ?>
        <?php if (can('manage_announcements')): ?>
        <a href="<?= url('/admin/announcements/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-megaphone-fill me-1"></i>Announcements
        </a>
        <?php endif; ?>
        <?php if (can('manage_resources')): ?>
        <a href="<?= url('/admin/resources/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-journal-richtext me-1"></i>Resources
        </a>
        <?php endif; ?>
        <?php if (can('manage_users')): ?>
        <a href="<?= url('/admin/users/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-people-fill me-1"></i>Users
        </a>
        <?php endif; ?>
        <?php if (can('view_analytics')): ?>
        <a href="<?= url('/admin/analytics/') ?>" class="btn btn-paw-outline">
            <i class="bi bi-bar-chart-fill me-1"></i>Analytics
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Recent Reports ─────────────────────────────────────────────────── -->
<?php if (can('manage_reports') && !empty($recentReports)): ?>
<div class="paw-card card shadow-sm">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Reports</span>
        <a href="<?= url('/admin/reports/') ?>" class="btn btn-sm btn-paw-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title / Reporter</th>
                    <th>Status</th>
                    <th>Urgency</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentReports as $rpt): ?>
            <tr>
                <td class="text-muted small"><?= (int)$rpt['id'] ?></td>
                <td>
                    <div class="fw-semibold"><?= e($rpt['title'] ?: '(No title)') ?></div>
                    <div class="text-muted small"><?= e($rpt['reporter_name'] ?? '—') ?></div>
                </td>
                <td>
                    <span class="badge-status--<?= e($rpt['status']) ?>">
                        <?= e(status_label($rpt['status'])) ?>
                    </span>
                </td>
                <td>
                    <span class="urgency--<?= e($rpt['urgency']) ?>">
                        <?= e(ucfirst($rpt['urgency'])) ?>
                    </span>
                </td>
                <td class="text-muted small">
                    <?= e(date('M j', strtotime($rpt['created_at']))) ?>
                </td>
                <td>
                    <a href="<?= url('/admin/reports/view.php?id=' . (int)$rpt['id']) ?>"
                       class="btn btn-sm btn-paw-outline">
                        View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php
layout_footer();
