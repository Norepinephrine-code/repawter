</main>

<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6 class="fw-bold">🐾 <?= e(APP_NAME) ?></h6>
                <p class="small text-muted mb-0">Connecting communities with animal welfare services.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('/') ?>" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="<?= url('/adoption/') ?>" class="text-muted text-decoration-none">Adoption Gallery</a></li>
                    <li><a href="<?= url('/resources/') ?>" class="text-muted text-decoration-none">Resources</a></li>
                    <li><a href="<?= url('/announcements/') ?>" class="text-muted text-decoration-none">Announcements</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Get Involved</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('/reports/submit.php') ?>" class="text-muted text-decoration-none">Report a Stray</a></li>
                    <li><a href="<?= url('/foster/') ?>" class="text-muted text-decoration-none">Become a Foster</a></li>
                    <li><a href="<?= url('/auth/register.php') ?>" class="text-muted text-decoration-none">Create an Account</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built with care for animals and communities.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>

<script src="<?= asset('/js/app.js') ?>"></script>
<?php foreach ($extraJs as $js): ?>
<?php if (str_starts_with($js, '<script')): ?>
<?= $js ?>
<?php else: ?>
<script src="<?= e($js) ?>"></script>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>
