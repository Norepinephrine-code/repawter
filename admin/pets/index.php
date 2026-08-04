<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_ADMIN);

$filters = [
    'status'      => $_GET['status']      ?? '',
    'animal_type' => $_GET['animal_type'] ?? '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = PetModel::list_admin($filters, $page);
$pets   = $result['rows'];

$statusOptions = [
    ''                 => 'All Statuses',
    'draft'            => 'Draft',
    'available'        => 'Available',
    'pending_adoption' => 'Pending Adoption',
    'adopted'          => 'Adopted',
    'archived'         => 'Archived',
];

layout_header('Manage Pets', 'pets');
admin_nav('pets');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="paw-page-title mb-0">Manage Pets</h1>
        <a href="<?= url('/admin/pets/edit.php') ?>" class="btn btn-paw">
            <i class="bi bi-plus-circle me-1"></i> New Pet
        </a>
    </div>

    <form method="get" class="row g-2 mb-4">
        <div class="col-auto">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($statusOptions as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>>
                    <?= e($lbl) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="animal_type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="dog"   <?= $filters['animal_type'] === 'dog'   ? 'selected' : '' ?>>Dog</option>
                <option value="cat"   <?= $filters['animal_type'] === 'cat'   ? 'selected' : '' ?>>Cat</option>
                <option value="other" <?= $filters['animal_type'] === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-secondary">Filter</button>
            <a href="<?= url('/admin/pets/index.php') ?>" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">Photo</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Breed</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pets)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No pets found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($pets as $pet): ?>
                <?php
                $thumb = !empty($pet['photo_path'])
                    ? upload_url($pet['photo_path'])
                    : asset('/img/placeholder-pet.png');
                $typeLabel = match ($pet['animal_type']) {
                    'dog'   => 'Dog',
                    'cat'   => 'Cat',
                    default => $pet['species_other'] ?? 'Other',
                };
                $statusBadge = [
                    'draft'            => 'secondary',
                    'available'        => 'success',
                    'pending_adoption' => 'warning',
                    'adopted'          => 'info',
                    'archived'         => 'dark',
                ][$pet['status']] ?? 'secondary';
                ?>
                <tr>
                    <td>
                        <img src="<?= e($thumb) ?>" alt=""
                             style="width:50px;height:50px;object-fit:cover;" class="rounded">
                    </td>
                    <td class="fw-semibold"><?= e($pet['name']) ?></td>
                    <td><?= e($typeLabel) ?></td>
                    <td><?= e($pet['breed'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $statusBadge ?>">
                            <?= e(ucwords(str_replace('_', ' ', $pet['status']))) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e(date('M d, Y', strtotime($pet['created_at']))) ?></td>
                    <td>
                        <a href="<?= url('/admin/pets/edit.php?id=' . (int)$pet['id']) ?>"
                           class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <?php if ($pet['status'] !== 'archived'): ?>
                        <form method="post" action="<?= url('/actions/pets/pet_delete.php') ?>"
                              class="d-inline"
                              onsubmit="return confirm('Archive this pet?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$pet['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-archive"></i> Archive
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $qStr = http_build_query(array_filter($filters));
    $sep  = $qStr ? '&' : '';
    echo render_pager($result, url('/admin/pets/index.php?' . $qStr . $sep . 'page=%d'));
    ?>
</div>

<?php layout_footer(); ?>
