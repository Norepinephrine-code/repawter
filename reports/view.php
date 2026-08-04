<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$id     = (int) ($_GET['id'] ?? 0);
$report = $id > 0 ? ReportModel::find_with_details($id) : null;

if ($report === null) {
    flash('error', 'Report not found.');
    redirect('/reports/my_reports.php');
}

if ((int) $report['reporter_id'] !== (int) user_id()) {
    flash('error', 'You do not have permission to view that report.');
    redirect('/reports/my_reports.php');
}

$history = ReportModel::status_history($id);
$locked  = (bool) $report['is_locked'];

$urgencyLabels = [
    'low'      => 'Low',
    'medium'   => 'Medium',
    'high'     => 'High',
    'critical' => 'Critical',
];

$animalLabels = [
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    'other' => 'Other',
];

layout_header('Report #' . e($id), 'my_reports');
?>

<div class="container py-4">

    <a href="<?= url('/reports/my_reports.php') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to My Reports
    </a>

    <?php if ($locked): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-lock-fill"></i>
        <span>This report is now under review and <strong>can no longer be edited</strong>.</span>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-md-4">
            <?php if (!empty($report['photo_path'])): ?>
            <img src="<?= e(upload_url($report['photo_path'])) ?>"
                 alt="Report photo"
                 class="report-photo img-fluid rounded mb-3 w-100"
                 style="object-fit:cover;max-height:340px;">
            <?php endif; ?>

            <div class="paw-card p-3 mb-3">
                <p class="mb-2 fw-semibold text-paw-dark">Status</p>
                <?php partial('report_status_badge', ['status' => $report['status']]) ?>
            </div>

            <div class="paw-card p-3">
                <p class="mb-2 fw-semibold text-paw-dark">Urgency</p>
                <span class="badge urgency--<?= e($report['urgency']) ?>">
                    <?= e($urgencyLabels[$report['urgency']] ?? ucfirst($report['urgency'])) ?>
                </span>
            </div>
        </div>

        <div class="col-md-8">
            <div class="paw-card p-4 mb-4">
                <h2 class="h4 text-paw-dark mb-3">
                    <?= e($report['title'] ?: ucfirst($report['animal_type']) . ' report #' . $report['id']) ?>
                </h2>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Animal</dt>
                    <dd class="col-sm-8">
                        <?= e($animalLabels[$report['animal_type']] ?? ucfirst($report['animal_type'])) ?>
                        <?php if ($report['animal_type'] === 'other' && !empty($report['species_other'])): ?>
                        — <?= e($report['species_other']) ?>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Barangay</dt>
                    <dd class="col-sm-8"><?= e($report['barangay_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Location</dt>
                    <dd class="col-sm-8"><?= e($report['location_address']) ?></dd>

                    <?php if (!empty($report['location_landmark'])): ?>
                    <dt class="col-sm-4">Landmark</dt>
                    <dd class="col-sm-8"><?= e($report['location_landmark']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4">Submitted</dt>
                    <dd class="col-sm-8"><?= e(date('F j, Y g:i A', strtotime($report['created_at']))) ?></dd>

                    <?php if (!empty($report['assigned_to_name'])): ?>
                    <dt class="col-sm-4">Assigned To</dt>
                    <dd class="col-sm-8"><?= e($report['assigned_to_name']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($report['verified_by_name'])): ?>
                    <dt class="col-sm-4">Verified By</dt>
                    <dd class="col-sm-8"><?= e($report['verified_by_name']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="paw-card p-4 mb-4">
                <h3 class="h6 fw-semibold text-paw-dark mb-2">Description</h3>
                <p class="mb-0" style="white-space:pre-wrap;"><?= e($report['description']) ?></p>
            </div>

            <?php if (!empty($history)): ?>
            <div class="paw-card p-4">
                <h3 class="h6 fw-semibold text-paw-dark mb-3">
                    <i class="bi bi-clock-history"></i> Status Timeline
                </h3>
                <ul class="list-group list-group-flush">
                    <?php foreach ($history as $h): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-arrow-right-circle text-paw mt-1"></i>
                            <div>
                                <span class="fw-semibold">
                                    <?= e(ucfirst(str_replace('_', ' ', $h['to_status']))) ?>
                                </span>
                                <?php if (!empty($h['from_status'])): ?>
                                <span class="text-muted small">
                                    (from <?= e(ucfirst(str_replace('_', ' ', $h['from_status']))) ?>)
                                </span>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <?= e(date('M j, Y g:i A', strtotime($h['created_at']))) ?>
                                    <?php if (!empty($h['changer_name'])): ?>
                                    &middot; by <?= e($h['changer_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($h['note'])): ?>
                                <div class="mt-1 fst-italic text-muted small">
                                    <?= e($h['note']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
layout_footer();
