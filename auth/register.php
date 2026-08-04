<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

if (is_logged_in()) {
    redirect('/');
}

$barangays = db_all(
    'SELECT id, name, municipality FROM barangays WHERE is_active = 1 ORDER BY name'
);

layout_header('Register', 'register');
?>

<div class="row justify-content-center">
    <div class="col-sm-10 col-md-8 col-lg-6">
        <div class="paw-card card p-4 mt-3">
            <div class="text-center mb-4">
                <h2 class="paw-page-title mb-1">Create an Account</h2>
                <p class="text-muted small">Join the RePawter community</p>
            </div>

            <form method="post" action="<?= url('/actions/auth/register_submit.php') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="full_name"
                        name="full_name"
                        value="<?= e(old('full_name')) ?>"
                        maxlength="150"
                        placeholder="Juan dela Cruz"
                        required
                        autocomplete="name"
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= e(old('email')) ?>"
                        placeholder="you@example.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label fw-semibold">Contact Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="contact_number"
                        name="contact_number"
                        value="<?= e(old('contact_number')) ?>"
                        placeholder="09xxxxxxxxx (optional)"
                        autocomplete="tel"
                    >
                </div>

                <div class="mb-3">
                    <label for="barangay_id" class="form-label fw-semibold">Barangay</label>
                    <select class="form-select" id="barangay_id" name="barangay_id">
                        <option value="">— Select your barangay —</option>
                        <?php foreach ($barangays as $brgy): ?>
                        <option value="<?= e($brgy['id']) ?>"
                            <?= (old('barangay_id') == $brgy['id']) ? 'selected' : '' ?>>
                            <?= e($brgy['name']) ?>
                            <?php if ($brgy['municipality']): ?>
                                &mdash; <?= e($brgy['municipality']) ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        minlength="8"
                        placeholder="At least 8 characters"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="password_confirm"
                        name="password_confirm"
                        minlength="8"
                        placeholder="Repeat your password"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-paw btn-lg">
                        <i class="bi bi-person-plus me-1"></i> Create Account
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <p class="text-center mb-0 small">
                Already have an account?
                <a href="<?= url('/auth/login.php') ?>" class="text-paw fw-semibold">Sign in here</a>
            </p>
        </div>
    </div>
</div>

<?php
layout_footer();
