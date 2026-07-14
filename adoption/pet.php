<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$id  = (int)($_GET['id'] ?? 0);
$pet = $id > 0 ? PetModel::find($id) : null;

if ($pet === null) {
    flash('error', 'Pet not found.');
    redirect('/adoption/gallery.php');
}

$photoUrl = !empty($pet['photo_path'])
    ? upload_url($pet['photo_path'])
    : asset('/img/placeholder-pet.png');

$typeLabel = match ($pet['animal_type']) {
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    default => $pet['species_other'] ?? 'Other',
};
$sexLabel = match ($pet['sex']) {
    'male'   => 'Male',
    'female' => 'Female',
    default  => 'Unknown',
};
$ageLabel = '';
if (!empty($pet['approx_age_months'])) {
    $months = (int)$pet['approx_age_months'];
    $ageLabel = $months < 12
        ? $months . ' month' . ($months !== 1 ? 's' : '')
        : round($months / 12, 1) . ' year' . (round($months / 12, 1) !== 1.0 ? 's' : '');
}

layout_header(e($pet['name']) . ' — Pet Profile', 'gallery');
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/adoption/gallery.php') ?>">Adopt a Pet</a></li>
            <li class="breadcrumb-item active"><?= e($pet['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Photo -->
        <div class="col-md-5">
            <img src="<?= e($photoUrl) ?>"
                 alt="Photo of <?= e($pet['name']) ?>"
                 class="pet-photo img-fluid rounded shadow w-100"
                 style="max-height:400px; object-fit:cover;">
        </div>

        <!-- Details -->
        <div class="col-md-7">
            <h1 class="paw-page-title"><?= e($pet['name']) ?></h1>

            <dl class="row mt-3">
                <dt class="col-sm-4">Animal Type</dt>
                <dd class="col-sm-8"><?= e($typeLabel) ?></dd>

                <?php if (!empty($pet['breed'])): ?>
                <dt class="col-sm-4">Breed</dt>
                <dd class="col-sm-8"><?= e($pet['breed']) ?></dd>
                <?php endif; ?>

                <dt class="col-sm-4">Sex</dt>
                <dd class="col-sm-8"><?= e($sexLabel) ?></dd>

                <?php if ($ageLabel !== ''): ?>
                <dt class="col-sm-4">Approximate Age</dt>
                <dd class="col-sm-8"><?= e($ageLabel) ?></dd>
                <?php endif; ?>

                <?php if (!empty($pet['size'])): ?>
                <dt class="col-sm-4">Size</dt>
                <dd class="col-sm-8"><?= e(ucfirst($pet['size'])) ?></dd>
                <?php endif; ?>

                <?php if (!empty($pet['color'])): ?>
                <dt class="col-sm-4">Color / Markings</dt>
                <dd class="col-sm-8"><?= e($pet['color']) ?></dd>
                <?php endif; ?>

                <dt class="col-sm-4">Vaccinated</dt>
                <dd class="col-sm-8">
                    <?php if ($pet['is_vaccinated'] === null): ?>
                        <span class="text-muted">Unknown</span>
                    <?php elseif ($pet['is_vaccinated']): ?>
                        <span class="badge bg-success">Yes</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">No</span>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-4">Neutered / Spayed</dt>
                <dd class="col-sm-8">
                    <?php if ($pet['is_neutered'] === null): ?>
                        <span class="text-muted">Unknown</span>
                    <?php elseif ($pet['is_neutered']): ?>
                        <span class="badge bg-success">Yes</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">No</span>
                    <?php endif; ?>
                </dd>
            </dl>

            <?php if (!empty($pet['description'])): ?>
            <h5 class="fw-semibold mt-2">About <?= e($pet['name']) ?></h5>
            <p><?= nl2br(e($pet['description'])) ?></p>
            <?php endif; ?>

            <!-- Adoption button area -->
            <div class="mt-4">
                <?php if ($pet['status'] !== 'available'): ?>
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <?= e(ucwords(str_replace('_', ' ', $pet['status']))) ?>
                    </span>
                <?php elseif (!is_logged_in()): ?>
                    <a href="<?= url('/auth/login.php?redirect=' . urlencode('/adoption/apply.php?pet_id=' . $id)) ?>"
                       class="btn btn-paw btn-lg">
                        <i class="bi bi-heart me-1"></i> Log In to Apply for Adoption
                    </a>
                <?php elseif (user_role() === ROLE_RESIDENT): ?>
                    <a href="<?= url('/adoption/apply.php?pet_id=' . $id) ?>"
                       class="btn btn-paw btn-lg">
                        <i class="bi bi-heart me-1"></i> Apply to Adopt
                    </a>
                <?php else: ?>
                    <p class="text-muted"><em>Adoption applications are available to community residents.</em></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
