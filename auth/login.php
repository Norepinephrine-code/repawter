<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

if (is_logged_in()) {
    redirect('/');
}

layout_header('Login', 'login');
?>

<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="paw-card card p-4 mt-3">
            <div class="text-center mb-4">
                <h2 class="paw-page-title mb-1">Welcome Back</h2>
                <p class="text-muted small">Sign in to your RePawter account</p>
            </div>

            <form method="post" action="<?= url('/actions/auth/login_submit.php') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
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
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Your password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-paw btn-lg">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <p class="text-center mb-0 small">
                Don&rsquo;t have an account?
                <a href="<?= url('/auth/register.php') ?>" class="text-paw fw-semibold">Register here</a>
            </p>
        </div>
    </div>
</div>

<?php
layout_footer();
