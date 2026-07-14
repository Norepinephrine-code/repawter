<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) {
    $role = user_role();
    if (in_array($role, [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true)) {
        redirect('/admin/dashboard.php');
    }

    // Resident landing page
    layout_header('Home', 'home');
?>
<section class="paw-hero mb-5">
    <h1>Welcome back, <?= e(current_user()['full_name'] ?? 'Friend') ?>! 🐾</h1>
    <p class="mb-4">Help us keep our community safe for animals. Report a stray, foster a pet, or find your new best friend.</p>
    <div class="d-flex flex-wrap gap-3 justify-content-center">
        <a href="<?= url('/reports/submit.php') ?>" class="btn btn-paw btn-lg">
            <i class="bi bi-flag-fill"></i> Report an Animal
        </a>
        <a href="<?= url('/adoption/') ?>" class="btn btn-paw-outline btn-lg text-white border-white">
            <i class="bi bi-heart-fill"></i> Adopt a Pet
        </a>
        <a href="<?= url('/foster/') ?>" class="btn btn-paw-outline btn-lg text-white border-white">
            <i class="bi bi-house-heart-fill"></i> Become a Foster
        </a>
    </div>
</section>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <a href="<?= url('/reports/submit.php') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-flag-fill text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">Report a Stray</h5>
                <p class="text-muted small">Spotted an animal in distress? Let us know immediately.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= url('/reports/') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-clipboard2-check-fill text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">My Reports</h5>
                <p class="text-muted small">Track the status of your submitted animal reports.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= url('/adoption/') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-heart-fill text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">Adopt a Pet</h5>
                <p class="text-muted small">Browse adorable animals looking for their forever home.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= url('/announcements/') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-megaphone-fill text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">Announcements</h5>
                <p class="text-muted small">Stay updated on vaccination drives and community events.</p>
            </div>
        </a>
    </div>
</div>

<section class="mb-4">
    <h3 class="paw-page-title">Pets Looking for a Home</h3>
    <p class="text-muted">Visit our <a href="<?= url('/adoption/') ?>">Adoption Gallery</a> to meet all available animals.</p>
</section>
<?php
    layout_footer();
    exit;
}

// Guest landing page
layout_header('Welcome', 'home');
?>
<section class="paw-hero mb-5">
    <h1>🐾 RePawter</h1>
    <p class="mb-2 fs-5">Community-Powered Animal Welfare</p>
    <p class="mb-4">Report stray and distressed animals, connect with rescue organizations,
       foster and adopt pets in your community — all in one place.</p>
    <div class="d-flex flex-wrap gap-3 justify-content-center">
        <a href="<?= url('/auth/register.php') ?>" class="btn btn-paw btn-lg">
            <i class="bi bi-person-plus-fill"></i> Join RePawter
        </a>
        <a href="<?= url('/auth/login.php') ?>" class="btn btn-paw-outline btn-lg text-white border-white">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </a>
    </div>
</section>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="paw-card p-4 text-center h-100">
            <i class="bi bi-flag-fill text-paw fs-2 mb-3 d-block"></i>
            <h5 class="fw-bold">Report Animals</h5>
            <p class="text-muted small">Community members can report stray or injured animals quickly and easily.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="paw-card p-4 text-center h-100">
            <i class="bi bi-shield-check-fill text-paw fs-2 mb-3 d-block"></i>
            <h5 class="fw-bold">Verified Response</h5>
            <p class="text-muted small">Barangay officials and welfare orgs review, verify, and respond to reports.</p>
        </div>
    </div>
    <div class="col-md-3">
        <a href="<?= url('/adoption/') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-heart-fill text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">Adoption Gallery</h5>
                <p class="text-muted small">Browse pets available for adoption and give them a forever home.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= url('/resources/') ?>" class="text-decoration-none">
            <div class="paw-card p-4 text-center h-100">
                <i class="bi bi-journal-richtext text-paw fs-2 mb-3 d-block"></i>
                <h5 class="fw-bold">Resources</h5>
                <p class="text-muted small">Educational guides on responsible pet ownership, TNR, and more.</p>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="paw-card p-4 h-100">
            <h4 class="fw-bold text-paw-dark">Latest Announcements</h4>
            <p class="text-muted">Stay informed about vaccination drives, adoption events, and TNR schedules in your area.</p>
            <a href="<?= url('/announcements/') ?>" class="btn btn-paw-outline">
                <i class="bi bi-megaphone"></i> View Announcements
            </a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="paw-card p-4 h-100">
            <h4 class="fw-bold text-paw-dark">Adopt &amp; Foster</h4>
            <p class="text-muted">Ready to give an animal a second chance? Browse our gallery or apply to be a foster parent.</p>
            <a href="<?= url('/adoption/') ?>" class="btn btn-paw me-2">
                <i class="bi bi-heart"></i> Adopt
            </a>
            <a href="<?= url('/foster/') ?>" class="btn btn-paw-outline">
                <i class="bi bi-house-heart"></i> Foster
            </a>
        </div>
    </div>
</div>
<?php
layout_footer();
