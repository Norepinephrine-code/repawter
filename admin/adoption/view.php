<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_ADMIN);

$id  = (int)($_GET['id'] ?? 0);
$app = $id > 0 ? AdoptionModel::find_with_details($id) : null;

if ($app === null) {
    flash('error', 'Application not found.');
    redirect('/admin/adoption/index.php');
}

$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'approved'     => 'Approved',
    'rejected'     => 'Rejected',
    'withdrawn'    => 'Withdrawn',
    'completed'    => 'Completed',
];
$statusLabel = $statusLabels[$app['status']] ?? ucfirst($app['status']);

$petPhoto = photo_url($app['pet_photo'] ?? null);

$petTypeLabel = match ($app['pet_animal_type'] ?? 'other') {
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    default => $app['pet_species_other'] ?? 'Other',
};

$agreement = AdoptionModel::find_agreement($id);

layout_header('Application #' . $id, 'adoption');
admin_nav('adoption');
?>

<div class="container py-4" style="max-width:860px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/admin/adoption/index.php') ?>">Applications</a></li>
            <li class="breadcrumb-item active">Application
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="paw-page-title mb-0">Adoption Application
        <span class="badge fs-6 px-3 py-2
            <?= in_array($app['status'], ['approved','completed'], true) ? 'bg-success' : ($app['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary') ?>">
            <?= e($statusLabel) ?>
        </span>
    </div>

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
                        <dd class="col-sm-7"><?= e($app['applicant_email']) ?></dd>
                        <dt class="col-sm-5">Contact</dt>
                        <dd class="col-sm-7"><?= e($app['applicant_contact_number'] ?? '—') ?></dd>
                        <dt class="col-sm-5">Home Type</dt>
                        <dd class="col-sm-7"><?= e($app['home_type'] ?? '—') ?></dd>
                        <dt class="col-sm-5">Other Pets?</dt>
                        <dd class="col-sm-7">
                            <?php if ($app['has_other_pets'] === null): ?>
                                <span class="text-muted">Not specified</span>
                            <?php else: ?>
                                <?= $app['has_other_pets'] ? 'Yes' : 'No' ?>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-5">Date Applied</dt>
                        <dd class="col-sm-7"><?= e(date('M d, Y g:i A', strtotime($app['created_at']))) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card paw-card h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-heart me-1"></i> Pet Information
                </div>
                <div class="card-body d-flex gap-3">
                    <img src="<?= e($petPhoto) ?>" alt=""
                         style="width:90px;height:90px;object-fit:cover;" class="rounded flex-shrink-0">
                    <dl class="row mb-0 flex-fill">
                        <dt class="col-sm-5">Name</dt>
                        <dd class="col-sm-7">
                            <a href="<?= url('/adoption/pet.php?id=' . (int)$app['pet_id']) ?>" target="_blank">
                                <?= e($app['pet_name']) ?>
                            </a>
                        </dd>
                        <dt class="col-sm-5">Type</dt>
                        <dd class="col-sm-7"><?= e($petTypeLabel) ?></dd>
                        <?php if (!empty($app['pet_breed'])): ?>
                        <dt class="col-sm-5">Breed</dt>
                        <dd class="col-sm-7"><?= e($app['pet_breed']) ?></dd>
                        <?php endif; ?>
                        <dt class="col-sm-5">Sex</dt>
                        <dd class="col-sm-7"><?= e(ucfirst($app['pet_sex'] ?? 'unknown')) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-chat-text me-1"></i> Applicant's Message
                </div>
                <div class="card-body">
                    <?php if (!empty($app['message'])): ?>
                        <p class="mb-0"><?= nl2br(e($app['message'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No message provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($app['decision_notes'])): ?>
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header fw-semibold text-warning-emphasis">Decision Notes</div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(e($app['decision_notes'])) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($app['status'], ['submitted', 'under_review'], true)): ?>
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Actions</div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <form method="post" action="<?= url('/actions/adoption/adoption_decide.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="btn btn-success w-100"
                                        data-confirm="Approve this application?">
                                    <i class="bi bi-check-circle me-1"></i> Approve
                                </button>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <form method="post" action="<?= url('/actions/adoption/adoption_decide.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input type="hidden" name="decision" value="reject">
                                <div class="mb-2">
                                    <textarea name="decision_notes" class="form-control form-control-sm"
                                              rows="2" placeholder="Reason for rejection (optional)"></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100"
                                        data-confirm="Reject this application?">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($app['status'] === 'approved'): ?>
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Mark as Completed</div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Marking as completed will set the pet's status to "Adopted" and finalize the application.
                    </p>
                    <form method="post" action="<?= url('/actions/adoption/adoption_decide.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="decision" value="complete">
                        <button type="submit" class="btn btn-paw-teal"
                                data-confirm="Mark this adoption as completed?">
                            <i class="bi bi-trophy me-1"></i> Mark as Completed
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($app['status'], ['approved', 'completed'], true)): ?>
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-semibold mb-1">Adoption Agreement PDF</h6>
                        <?php if ($agreement !== null): ?>
                        <p class="text-muted small mb-0">
                            Agreement
                            Generated <?= e(date('M d, Y', strtotime($agreement['generated_at']))) ?>
                        </p>
                        <?php else: ?>
                        <p class="text-muted small mb-0">No agreement generated yet.</p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('/admin/adoption/agreement_pdf.php?application_id=' . (int)$id) ?>"
                       class="btn btn-info text-white ms-3">
                        <i class="bi bi-file-earmark-pdf me-1"></i>
                        <?= $agreement !== null ? 'Download PDF' : 'Generate PDF' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="mt-3">
        <a href="<?= url('/admin/adoption/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Applications
        </a>
    </div>
</div>

<?php layout_footer(); ?>
