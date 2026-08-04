<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_OFFICIAL, ROLE_ADMIN);

$id  = (int)($_GET['id'] ?? 0);
$app = $id > 0 ? FosterModel::find_with_applicant($id) : null;

if ($app === null) {
    flash('error', 'Foster application not found.');
    redirect('/admin/foster/index.php');
}

$history = FosterModel::applicant_history((int)$app['applicant_id']);

$statusBadge = [
    'submitted'    => 'bg-secondary',
    'under_review' => 'bg-primary',
    'approved'     => 'bg-success',
    'rejected'     => 'bg-danger',
    'withdrawn'    => 'bg-dark',
];
$badgeClass = $statusBadge[$app['status']] ?? 'bg-secondary';
$statusText = ucwords(str_replace('_', ' ', $app['status']));

$typeLabel = [
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    'other' => 'Other',
];
$type = $typeLabel[$app['preferred_animal_type']] ?? ucfirst($app['preferred_animal_type']);

$capacityFull = (int)$app['current_foster_count'] >= (int)$app['household_capacity'];
$isPending    = in_array($app['status'], ['submitted', 'under_review'], true);

layout_header('Foster Application #' . $id, 'foster');
admin_nav('foster');
?>

<div class="container py-4" style="max-width:900px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= url('/admin/foster/index.php') ?>">Foster Applications</a>
            </li>
            <li class="breadcrumb-item active">Application
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="paw-page-title mb-0">Foster Application
        <span class="badge fs-6 px-3 py-2 <?= e($badgeClass) ?>">
            <?= e($statusText) ?>
        </span>
    </div>

    <?php if ($capacityFull && $isPending): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>Capacity Warning:</strong> This applicant's current foster count
            (<?= (int)$app['current_foster_count'] ?>) has reached their declared household capacity
            (<?= (int)$app['household_capacity'] ?>). Review carefully before approving.
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card paw-card h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-person me-1"></i> Applicant Information
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Name</dt>
                        <dd class="col-sm-7"><?= e($app['applicant_full_name']) ?></dd>

                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7">
                            <a href="mailto:<?= e($app['applicant_email']) ?>">
                                <?= e($app['applicant_email']) ?>
                            </a>
                        </dd>

                        <dt class="col-sm-5">Contact</dt>
                        <dd class="col-sm-7"><?= e($app['applicant_contact_number'] ?? '—') ?></dd>

                        <dt class="col-sm-5">Barangay</dt>
                        <dd class="col-sm-7"><?= e($app['applicant_barangay'] ?? '—') ?></dd>

                        <dt class="col-sm-5">Date Applied</dt>
                        <dd class="col-sm-7"><?= e(date('M d, Y g:i A', strtotime($app['created_at']))) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card paw-card h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-house-heart me-1"></i> Foster Preferences
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Preferred Type</dt>
                        <dd class="col-sm-7"><?= e($type) ?></dd>

                        <dt class="col-sm-5">Capacity</dt>
                        <dd class="col-sm-7
                            <?= $capacityFull ? 'text-danger fw-semibold' : '' ?>">
                            <?= (int)$app['current_foster_count'] ?> / <?= (int)$app['household_capacity'] ?>
                            <?= $capacityFull ? '<span class="badge bg-danger ms-1">Full</span>' : '' ?>
                        </dd>

                        <dt class="col-sm-5">Has Yard</dt>
                        <dd class="col-sm-7">
                            <?php if ($app['has_yard'] === null): ?>
                                <span class="text-muted">Not specified</span>
                            <?php else: ?>
                                <?= $app['has_yard'] ? 'Yes' : 'No' ?>
                            <?php endif; ?>
                        </dd>
                    </dl>

                    <?php if (!empty($app['preferred_notes'])): ?>
                    <hr class="my-2">
                    <p class="small mb-1 fw-semibold">Preference Notes</p>
                    <p class="small mb-0"><?= nl2br(e($app['preferred_notes'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($app['household_notes'])): ?>
                    <hr class="my-2">
                    <p class="small mb-1 fw-semibold">Household Notes</p>
                    <p class="small mb-0"><?= nl2br(e($app['household_notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($app['decision_notes'])): ?>
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header fw-semibold text-warning-emphasis">
                    <i class="bi bi-chat-square-text me-1"></i> Decision Notes
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(e($app['decision_notes'])) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isPending): ?>
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-check2-square me-1"></i> Review Decision
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <form method="post" action="<?= url('/actions/foster/foster_decide.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input type="hidden" name="decision" value="approve">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Decision Notes (optional)</label>
                                    <textarea name="decision_notes" class="form-control form-control-sm"
                                              rows="2" maxlength="500"
                                              placeholder="Any notes for the applicant…"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100"
                                        onclick="return confirm('Approve this foster application?')">
                                    <i class="bi bi-check-circle me-1"></i> Approve
                                </button>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <form method="post" action="<?= url('/actions/foster/foster_decide.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input type="hidden" name="decision" value="reject">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Reason for rejection (optional)</label>
                                    <textarea name="decision_notes" class="form-control form-control-sm"
                                              rows="2" maxlength="500"
                                              placeholder="Provide a reason…"></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100"
                                        onclick="return confirm('Reject this foster application?')">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-clock-history me-1"></i> Applicant's Foster History
                </div>
                <div class="card-body p-0">
                    <?php if (empty($history)): ?>
                    <p class="text-muted p-3 mb-0">No prior foster applications.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $histBadge = [
                                    'submitted'    => 'badge-status--submitted',
                                    'under_review' => 'badge-status--under_review',
                                    'approved'     => 'badge-status--resolved',
                                    'rejected'     => 'badge-status--rejected',
                                    'withdrawn'    => 'badge-status--archived',
                                ];
                                foreach ($history as $h):
                                    $hBadge  = $histBadge[$h['status']] ?? 'badge-status--submitted';
                                    $hStatus = ucwords(str_replace('_', ' ', $h['status']));
                                    $hType   = $typeLabel[$h['preferred_animal_type']] ?? ucfirst($h['preferred_animal_type']);
                                ?>
                                <tr <?= (int)$h['id'] === $id ? 'class="table-active"' : '' ?>>
                                    <td class="text-muted small">
                                    <td><?= e($hType) ?></td>
                                    <td><?= (int)$h['current_foster_count'] ?> / <?= (int)$h['household_capacity'] ?></td>
                                    <td><span class="badge <?= e($hBadge) ?>"><?= e($hStatus) ?></span></td>
                                    <td class="small text-muted"><?= e(date('M d, Y', strtotime($h['created_at']))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <a href="<?= url('/admin/foster/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Applications
        </a>
    </div>
</div>

<?php layout_footer(); ?>
