<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

require_login();

$user      = UserModel::find(user_id());
$barangays = db_all(
    'SELECT id, name, municipality FROM barangays WHERE is_active = 1 ORDER BY name'
);

layout_header('My Profile', 'profile');
?>

<div class="row justify-content-center">
    <div class="col-sm-12 col-md-10 col-lg-8">

        <h2 class="paw-page-title mb-4">My Profile</h2>

        <!-- ─── Profile Details Form ─── -->
        <div class="paw-card card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-paw"></i>Account Information</h5>

            <form method="post" action="<?= url('/actions/auth/profile_update.php') ?>" novalidate>
                <?= csrf_field() ?>

                <!-- Email — read-only display -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input
                        type="email"
                        class="form-control"
                        value="<?= e($user['email']) ?>"
                        readonly
                        disabled
                    >
                    <div class="form-text">Your email address cannot be changed here.</div>
                </div>

                <?php if ($user['role'] === ROLE_WELFARE && $user['organization_name']): ?>
                <!-- Organization name — read-only for welfare_org -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Organization Name</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= e($user['organization_name']) ?>"
                        readonly
                        disabled
                    >
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="full_name"
                        name="full_name"
                        value="<?= e(old('full_name', $user['full_name'] ?? '')) ?>"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label fw-semibold">Contact Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="contact_number"
                        name="contact_number"
                        value="<?= e(old('contact_number', $user['contact_number'] ?? '')) ?>"
                        placeholder="09xxxxxxxxx (optional)"
                    >
                </div>

                <div class="mb-4">
                    <label for="barangay_id" class="form-label fw-semibold">Barangay</label>
                    <select class="form-select" id="barangay_id" name="barangay_id">
                        <option value="">— Select your barangay —</option>
                        <?php
                        $selectedBrgy = old('barangay_id', (string)($user['barangay_id'] ?? ''));
                        foreach ($barangays as $brgy):
                        ?>
                        <option value="<?= e($brgy['id']) ?>"
                            <?= ($selectedBrgy == $brgy['id']) ? 'selected' : '' ?>>
                            <?= e($brgy['name']) ?>
                            <?php if ($brgy['municipality']): ?>
                                &mdash; <?= e($brgy['municipality']) ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-paw">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- ─── Change Password Form ─── -->
        <div class="paw-card card p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-lock me-2 text-paw"></i>Change Password</h5>

            <form method="post" action="<?= url('/actions/auth/password_update.php') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="current_password" class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="current_password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                    >
                </div>

                <div class="mb-4">
                    <label for="new_password_confirm" class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="new_password_confirm"
                        name="new_password_confirm"
                        minlength="8"
                        required
                        autocomplete="new-password"
                        placeholder="Repeat new password"
                    >
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-paw-teal">
                        <i class="bi bi-key me-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php
layout_footer();
