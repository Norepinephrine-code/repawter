<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_ADMIN);

$id  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$pet = ($id !== null && $id > 0) ? PetModel::find($id) : null;
$isNew = ($pet === null);

$old = peek_old();
$v   = function (string $key, string $default = '') use ($pet, $old): string {
    if (isset($old[$key])) {
        return (string)$old[$key];
    }
    return isset($pet[$key]) ? (string)$pet[$key] : $default;
};

$pageTitle = $isNew ? 'Add New Pet' : 'Edit Pet — ' . e($pet['name']);

layout_header($pageTitle, 'pets');
admin_nav('pets');
?>

<div class="container py-4" style="max-width:860px;">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/admin/pets/index.php') ?>">Pets</a></li>
            <li class="breadcrumb-item active"><?= $isNew ? 'New Pet' : e($pet['name']) ?></li>
        </ol>
    </nav>

    <h1 class="paw-page-title mb-4"><?= $isNew ? 'Add New Pet' : 'Edit Pet' ?></h1>

    <div class="card paw-card">
        <div class="card-body">
            <form method="post" action="<?= url('/actions/pets/pet_save.php') ?>"
                  enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= (int)$pet['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($v('name')) ?>" required maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <?php foreach (['draft','available','pending_adoption','adopted','archived'] as $s): ?>
                            <option value="<?= $s ?>" <?= $v('status','draft') === $s ? 'selected' : '' ?>>
                                <?= e(ucwords(str_replace('_',' ',$s))) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Animal Type <span class="text-danger">*</span></label>
                        <select name="animal_type" class="form-select" required
                                onchange="document.getElementById('species_other_wrap').style.display=this.value==='other'?'block':'none'">
                            <option value="dog"   <?= $v('animal_type','dog') === 'dog'   ? 'selected' : '' ?>>Dog</option>
                            <option value="cat"   <?= $v('animal_type','dog') === 'cat'   ? 'selected' : '' ?>>Cat</option>
                            <option value="other" <?= $v('animal_type','dog') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="species_other_wrap"
                         style="display:<?= $v('animal_type','dog') === 'other' ? 'block' : 'none' ?>">
                        <label class="form-label fw-semibold">Species (if Other)</label>
                        <input type="text" name="species_other" class="form-control"
                               value="<?= e($v('species_other')) ?>" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Breed</label>
                        <input type="text" name="breed" class="form-control"
                               value="<?= e($v('breed')) ?>" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sex</label>
                        <select name="sex" class="form-select">
                            <?php foreach (['male','female','unknown'] as $s): ?>
                            <option value="<?= $s ?>" <?= $v('sex','unknown') === $s ? 'selected' : '' ?>>
                                <?= e(ucfirst($s)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Approximate Age (months)</label>
                        <input type="number" name="approx_age_months" class="form-control"
                               min="0" value="<?= e($v('approx_age_months')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Size</label>
                        <select name="size" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach (['small','medium','large'] as $s): ?>
                            <option value="<?= $s ?>" <?= $v('size') === $s ? 'selected' : '' ?>>
                                <?= e(ucfirst($s)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Color / Markings</label>
                        <input type="text" name="color" class="form-control"
                               value="<?= e($v('color')) ?>" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vaccinated?</label>
                        <select name="is_vaccinated" class="form-select">
                            <option value="">Unknown</option>
                            <option value="1" <?= $v('is_vaccinated') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= $v('is_vaccinated') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Neutered / Spayed?</label>
                        <select name="is_neutered" class="form-select">
                            <option value="">Unknown</option>
                            <option value="1" <?= $v('is_neutered') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= $v('is_neutered') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= e($v('description')) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Photo <?= $isNew ? '<span class="text-danger">*</span>' : '<span class="text-muted small">(leave blank to keep current)</span>' ?>
                        </label>
                        <?php if (!$isNew && !empty($pet['photo_path'])): ?>
                        <div class="mb-2">
                            <img src="<?= e(photo_url($pet['photo_path'] ?? null)) ?>"
                                 alt="Current photo" style="height:100px; object-fit:cover;" class="rounded border">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control"
                               accept="image/jpeg,image/png,image/webp"
                               <?= $isNew ? 'required' : '' ?>>
                        <div class="form-text">JPEG, PNG or WebP. Max 5 MB.</div>
                    </div>

                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= url('/admin/pets/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-paw">
                        <i class="bi bi-save me-1"></i> <?= $isNew ? 'Create Pet' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
