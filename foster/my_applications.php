<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

require_login();

$page   = max(1, (int)($_GET['page'] ?? 1));
$result = FosterModel::list_by_user((int)user_id(), $page);
$apps   = $result['rows'];

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

layout_header('My Foster Applications', 'foster');
?>

<div class="container py-4" style="max-width:900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="paw-page-title mb-0">My Foster Applications</h1>
        <a href="<?= url('/foster/apply.php') ?>" class="btn btn-paw">
            <i class="bi bi-plus-circle me-1"></i> New Application
        </a>
    </div>

    <?php if (empty($apps)): ?>
    <div class="card paw-card text-center py-5">
        <div class="card-body">
            <i class="bi bi-house-heart display-4 text-paw-dark mb-3"></i>
            <p class="text-muted mb-3">You haven't submitted any foster applications yet.</p>
            <a href="<?= url('/foster/apply.php') ?>" class="btn btn-paw">
                Apply to Foster
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($apps as $app): ?>
        <?php
            $badgeClass = $statusBadge[$app['status']] ?? 'badge-status--submitted';
            $statusText = ucwords(str_replace('_', ' ', $app['status']));
            $type       = $typeLabel[$app['preferred_animal_type']] ?? ucfirst($app['preferred_animal_type']);
        ?>
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 fw-semibold">
                                Application #<?= (int)$app['id'] ?>
                                &mdash; <?= e($type) ?>
                            </h5>
                            <div class="text-muted small mb-2">
                                Submitted <?= e(date('M d, Y', strtotime($app['created_at']))) ?>
                            </div>
                        </div>
                        <span class="badge <?= e($badgeClass) ?> fs-6 px-3 py-2">
                            <?= e($statusText) ?>
                        </span>
                    </div>

                    <dl class="row mb-2">
                        <dt class="col-sm-4">Household Capacity</dt>
                        <dd class="col-sm-8">
                            <?= (int)$app['current_foster_count'] ?> / <?= (int)$app['household_capacity'] ?> animals
                        </dd>

                        <?php if (!empty($app['preferred_notes'])): ?>
                        <dt class="col-sm-4">Preferences</dt>
                        <dd class="col-sm-8"><?= e($app['preferred_notes']) ?></dd>
                        <?php endif; ?>

                        <?php if ($app['has_yard'] !== null): ?>
                        <dt class="col-sm-4">Has Yard</dt>
                        <dd class="col-sm-8"><?= $app['has_yard'] ? 'Yes' : 'No' ?></dd>
                        <?php endif; ?>
                    </dl>

                    <?php if (!empty($app['decision_notes'])): ?>
                    <div class="alert alert-secondary small mb-0 mt-2">
                        <strong>Reviewer notes:</strong> <?= e($app['decision_notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        <?= render_pager($result, url('/foster/my_applications.php?page=%d')) ?>
    </div>
    <?php endif; ?>
</div>

<?php layout_footer(); ?>
