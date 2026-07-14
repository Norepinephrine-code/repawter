<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

require_login();

$petId = (int)($_GET['pet_id'] ?? 0);
$pet   = $petId > 0 ? PetModel::find($petId) : null;

if ($pet === null) {
    flash('error', 'Pet not found.');
    redirect('/adoption/gallery.php');
}

if ($pet['status'] !== 'available') {
    flash('error', 'This pet is no longer available for adoption.');
    redirect('/adoption/pet.php?id=' . $petId);
}

$photoUrl = !empty($pet['photo_path'])
    ? upload_url($pet['photo_path'])
    : asset('/img/placeholder-pet.png');

$typeLabel = match ($pet['animal_type']) {
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    default => $pet['species_other'] ?? 'Other',
};

layout_header('Apply to Adopt — ' . e($pet['name']), 'gallery');
?>

<div class="container py-4" style="max-width:720px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/adoption/gallery.php') ?>">Gallery</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/adoption/pet.php?id=' . $petId) ?>"><?= e($pet['name']) ?></a></li>
            <li class="breadcrumb-item active">Apply</li>
        </ol>
    </nav>

    <h1 class="paw-page-title mb-4">Apply to Adopt <?= e($pet['name']) ?></h1>

    <!-- Pet summary card -->
    <div class="card paw-card mb-4">
        <div class="row g-0">
            <div class="col-md-3">
                <img src="<?= e($photoUrl) ?>"
                     alt="<?= e($pet['name']) ?>"
                     class="img-fluid rounded-start"
                     style="height:130px; width:100%; object-fit:cover;">
            </div>
            <div class="col-md-9">
                <div class="card-body py-3">
                    <h5 class="card-title fw-bold mb-1"><?= e($pet['name']) ?></h5>
                    <p class="card-text text-muted small mb-0">
                        <?= e($typeLabel) ?>
                        <?php if (!empty($pet['breed'])): ?>
                            &bull; <?= e($pet['breed']) ?>
                        <?php endif; ?>
                    </p>
                    <span class="badge bg-success mt-1">Available</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Application form -->
    <div class="card paw-card">
        <div class="card-body">
            <form method="post" action="<?= url('/actions/adoption/adoption_submit.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="pet_id" value="<?= e($petId) ?>">

                <div class="mb-3">
                    <label for="message" class="form-label fw-semibold">
                        Why do you want to adopt <?= e($pet['name']) ?>? <span class="text-danger">*</span>
                    </label>
                    <textarea id="message" name="message" rows="4"
                              class="form-control"
                              placeholder="Tell us a little about yourself and why you'd be a great match..."
                              required><?= e(old('message')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="home_type" class="form-label fw-semibold">Home Type</label>
                    <input type="text" id="home_type" name="home_type" class="form-control"
                           placeholder="e.g. House with yard, Apartment, Condo..."
                           value="<?= e(old('home_type')) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Do you have other pets at home?</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_other_pets" id="hops_yes" value="1"
                                <?= old('has_other_pets') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="hops_yes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_other_pets" id="hops_no" value="0"
                                <?= old('has_other_pets') === '0' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="hops_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= url('/adoption/pet.php?id=' . $petId) ?>"
                       class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-paw">
                        <i class="bi bi-send me-1"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
