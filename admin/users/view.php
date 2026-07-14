<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid user ID.');
    redirect('/admin/users/');
}

$user = UserModel::find($id);
if (!$user) {
    flash('error', 'User not found.');
    redirect('/admin/users/');
}

// Activity summary
$reportCount = (int)(db_one(
    'SELECT COUNT(*) AS cnt FROM reports WHERE reporter_id = ?',
    [$id]
)['cnt'] ?? 0);

$fosterCount = (int)(db_one(
    'SELECT COUNT(*) AS cnt FROM foster_applications WHERE applicant_id = ?',
    [$id]
)['cnt'] ?? 0);

$adoptionCount = (int)(db_one(
    'SELECT COUNT(*) AS cnt FROM adoption_applications WHERE applicant_id = ?',
    [$id]
)['cnt'] ?? 0);

$roleLabels = [
    ROLE_RESIDENT => 'Community Resident',
    ROLE_OFFICIAL => 'Barangay Official',
    ROLE_WELFARE  => 'Welfare Org',
    ROLE_ADMIN    => 'System Admin',
];

$statusClass = match($user['account_status']) {
    'active'    => 'success',
    'flagged'   => 'warning',
    'suspended' => 'danger',
    default     => 'secondary',
};

layout_header('User: ' . $user['full_name'], 'admin');
admin_nav('users');
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="<?= url('/admin/users/') ?>" class="text-muted small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Users
            </a>
            <h1 class="paw-page-title mb-0 mt-1"><?= e($user['full_name']) ?></h1>
        </div>
        <span class="badge bg-<?= $statusClass ?> fs-6"><?= e(ucfirst($user['account_status'])) ?></span>
    </div>

    <div class="row g-4">
        <!-- Profile -->
        <div class="col-lg-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Profile Details</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID</dt>
                        <dd class="col-sm-8"><?= (int)$user['id'] ?></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><?= e($user['email']) ?></dd>

                        <dt class="col-sm-4">Role</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span>
                        </dd>

                        <dt class="col-sm-4">Contact</dt>
                        <dd class="col-sm-8"><?= e($user['contact_number'] ?? '—') ?></dd>

                        <dt class="col-sm-4">Barangay</dt>
                        <dd class="col-sm-8"><?= e($user['barangay_name'] ?? '—') ?></dd>

                        <dt class="col-sm-4">Organization</dt>
                        <dd class="col-sm-8"><?= e($user['organization_name'] ?? '—') ?></dd>

                        <dt class="col-sm-4">Member Since</dt>
                        <dd class="col-sm-8"><?= e(date('F d, Y', strtotime($user['created_at']))) ?></dd>

                        <dt class="col-sm-4">Last Login</dt>
                        <dd class="col-sm-8"><?= $user['last_login_at'] ? e(date('M d, Y g:i A', strtotime($user['last_login_at']))) : '—' ?></dd>

                        <?php if (!empty($user['flagged_reason'])): ?>
                        <dt class="col-sm-4 text-danger">Flag Reason</dt>
                        <dd class="col-sm-8 text-danger"><?= e($user['flagged_reason']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Activity Summary -->
        <div class="col-lg-6">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Activity Summary</div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="fs-2 fw-bold text-paw"><?= $reportCount ?></div>
                            <div class="small text-muted">Reports Submitted</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-2 fw-bold text-paw"><?= $fosterCount ?></div>
                            <div class="small text-muted">Foster Applications</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-2 fw-bold text-paw"><?= $adoptionCount ?></div>
                            <div class="small text-muted">Adoption Applications</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Status Controls -->
        <?php
        $myId    = user_id();
        $myRole  = user_role();
        $canEdit = ($id !== $myId); // cannot change own account
        // barangay_official may only change residents
        $isTargetStaff = in_array($user['role'], [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true);
        if ($myRole === ROLE_OFFICIAL && $isTargetStaff) {
            $canEdit = false;
        }
        ?>
        <?php if ($canEdit): ?>
        <div class="col-12">
            <div class="card paw-card">
                <div class="card-header fw-semibold">Account Status Controls</div>
                <div class="card-body">
                    <div class="row g-3">

                        <?php if ($user['account_status'] !== 'active'): ?>
                        <!-- Reactivate -->
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h6 class="text-success"><i class="bi bi-check-circle me-1"></i>Reactivate Account</h6>
                                <p class="small text-muted mb-3">Restore the user to active status.</p>
                                <form method="post" action="<?= url('/actions/users/user_status.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="status" value="active">
                                    <div class="mb-2">
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)" value="<?= e(old('reason')) ?>">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm w-100">Reactivate</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['account_status'] !== 'flagged'): ?>
                        <!-- Flag -->
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h6 class="text-warning"><i class="bi bi-flag me-1"></i>Flag Account</h6>
                                <p class="small text-muted mb-3">Mark account for review; user retains access.</p>
                                <form method="post" action="<?= url('/actions/users/user_status.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="status" value="flagged">
                                    <div class="mb-2">
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Flag reason" value="<?= e(old('reason')) ?>">
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm w-100"
                                            data-confirm="Flag this account?">Flag Account</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['account_status'] !== 'suspended'): ?>
                        <!-- Suspend -->
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h6 class="text-danger"><i class="bi bi-slash-circle me-1"></i>Suspend Account</h6>
                                <p class="small text-muted mb-3">Block user from logging in entirely.</p>
                                <form method="post" action="<?= url('/actions/users/user_status.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="status" value="suspended">
                                    <div class="mb-2">
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Suspension reason" value="<?= e(old('reason')) ?>">
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm w-100"
                                            data-confirm="Suspend this account? The user will not be able to log in.">Suspend</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($id === $myId): ?>
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-1"></i>
                You cannot modify your own account status.
            </div>
        </div>
        <?php elseif ($isTargetStaff && $myRole === ROLE_OFFICIAL): ?>
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                <i class="bi bi-shield me-1"></i>
                Only a System Admin can change the status of staff or admin accounts.
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php layout_footer(); ?>
