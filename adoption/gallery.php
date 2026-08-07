<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

$filters = [
    'animal_type' => isset($_GET['animal_type']) && in_array($_GET['animal_type'], ['dog', 'cat', 'other'], true)
        ? $_GET['animal_type']
        : '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = PetModel::list_public($filters, $page);
$pets   = $result['rows'];

layout_header('Adopt a Pet — Gallery', 'gallery');
?>

<section class="paw-hero text-center py-5 mb-4">
    <div class="container">
        <h1 class="display-5 fw-bold">Find Your Fur-ever Friend</h1>
        <p class="lead">Browse pets available for adoption in our community shelter.</p>
    </div>
</section>

<div class="container pb-5">

    <form method="get" action="<?= url('/adoption/gallery.php') ?>" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label class="form-label fw-semibold mb-1">Animal Type</label>
            <select name="animal_type" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="dog"   <?= $filters['animal_type'] === 'dog'   ? 'selected' : '' ?>>Dogs</option>
                <option value="cat"   <?= $filters['animal_type'] === 'cat'   ? 'selected' : '' ?>>Cats</option>
                <option value="other" <?= $filters['animal_type'] === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-paw">Filter</button>
            <?php if ($filters['animal_type'] !== ''): ?>
            <a href="<?= url('/adoption/gallery.php') ?>" class="btn btn-outline-secondary ms-1">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($pets)): ?>
        <div class="alert alert-info text-center">
            No pets are currently available for adoption. Check back soon!
        </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($pets as $p): ?>
        <div class="col">
            <?php partial('pet_card', ['pet' => $p]); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    $queryBase = $filters['animal_type'] !== ''
        ? url('/adoption/gallery.php?animal_type=' . urlencode($filters['animal_type']) . '&page=%d')
        : url('/adoption/gallery.php?page=%d');
    echo render_pager($result, $queryBase);
    ?>
</div>

<?php layout_footer(); ?>
