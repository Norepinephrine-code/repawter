<?php

$photoUrl = !empty($pet['photo_path'])
    ? upload_url($pet['photo_path'])
    : asset('/img/placeholder-pet.png');

$typeLabel = match ($pet['animal_type'] ?? 'other') {
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    default => $pet['species_other'] ?? 'Other',
};

$sexLabel = match ($pet['sex'] ?? 'unknown') {
    'male'    => 'Male',
    'female'  => 'Female',
    default   => 'Unknown',
};

$ageLabel = '';
if (!empty($pet['approx_age_months'])) {
    $months = (int)$pet['approx_age_months'];
    if ($months < 12) {
        $ageLabel = $months . ' mo' . ($months !== 1 ? 's' : '');
    } else {
        $yrs = round($months / 12, 1);
        $ageLabel = $yrs . ' yr' . ($yrs !== 1.0 ? 's' : '');
    }
}

$statusLabels = [
    'draft'            => 'Draft',
    'available'        => 'Available',
    'pending_adoption' => 'Pending Adoption',
    'adopted'          => 'Adopted',
    'archived'         => 'Archived',
];
$statusLabel = $statusLabels[$pet['status'] ?? ''] ?? ucfirst($pet['status'] ?? '');
?>
<div class="card pet-card h-100 shadow-sm">
    <img src="<?= e($photoUrl) ?>"
         class="card-img-top pet-card__img"
         alt="Photo of <?= e($pet['name'] ?? 'pet') ?>">
    <div class="card-body pet-card__body">
        <h5 class="card-title fw-bold mb-1"><?= e($pet['name'] ?? 'Unknown') ?></h5>
        <p class="card-text text-muted small mb-1">
            <?= e($typeLabel) ?>
            &bull; <?= e($sexLabel) ?>
            <?= $ageLabel !== '' ? '&bull; ' . e($ageLabel) : '' ?>
        </p>
        <?php if (!empty($pet['breed'])): ?>
        <p class="card-text text-muted small mb-2"><?= e($pet['breed']) ?></p>
        <?php endif; ?>
        <span class="pet-card__status badge mb-3
            <?= $pet['status'] === 'available' ? 'bg-success' : ($pet['status'] === 'adopted' ? 'bg-secondary' : 'bg-warning text-dark') ?>">
            <?= e($statusLabel) ?>
        </span>
    </div>
    <div class="card-footer bg-transparent border-top-0">
        <a href="<?= url('/adoption/pet.php?id=' . (int)($pet['id'] ?? 0)) ?>"
           class="btn btn-paw w-100">
            <i class="bi bi-eye"></i> View Profile
        </a>
    </div>
</div>
