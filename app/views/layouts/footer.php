<?php

declare(strict_types=1);
?>
</main>

<footer class="paw-footer mt-5">
    <div class="container">
        <div class="row g-4 py-4">
            <div class="col-lg-5">
                <p class="paw-footer__brand mb-2">
                    <span aria-hidden="true">🐾</span> <?= e(APP_NAME) ?>
                </p>
                <p class="paw-footer__tagline mb-0">
                    Connecting residents, barangay officials and animal-welfare
                    organizations — so no report goes unanswered and no animal
                    goes unnoticed.
                </p>
            </div>

            <div class="col-6 col-lg-3">
                <h2 class="paw-footer__heading">Explore</h2>
                <ul class="paw-footer__links list-unstyled mb-0">
                    <li><a href="<?= url('/') ?>">Home</a></li>
                    <li><a href="<?= url('/adoption/') ?>">Adoption Gallery</a></li>
                    <li><a href="<?= url('/resources/') ?>">Resources</a></li>
                    <li><a href="<?= url('/announcements/') ?>">Announcements</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h2 class="paw-footer__heading">Get involved</h2>
                <ul class="paw-footer__links list-unstyled mb-0">
                    <li><a href="<?= url('/reports/submit.php') ?>">Report a stray</a></li>
                    <li><a href="<?= url('/foster/') ?>">Become a foster</a></li>
<?php if (!is_logged_in()): ?>
                    <li><a href="<?= url('/auth/register.php') ?>">Create an account</a></li>
<?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="paw-footer__bar">
            <p class="mb-0">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built with care for animals and communities.
            </p>
        </div>
    </div>
</footer>

<script src="<?= asset('/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/js/app.js') ?>"></script>
<?php foreach ($extraJs as $js): ?>
<?php   if (str_starts_with($js, '<script')): ?>
<?= $js ?>

<?php   else: ?>
<script src="<?= e($js) ?>"></script>
<?php   endif; ?>
<?php endforeach; ?>
</body>
</html>
