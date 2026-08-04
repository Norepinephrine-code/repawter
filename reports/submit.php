<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_role(ROLE_RESIDENT);

$barangays = db_all("SELECT id, name FROM barangays WHERE is_active = 1 ORDER BY name ASC", []);

layout_header('Report an Animal', 'report');
?>

<div class="container py-4">
    <h1 class="paw-page-title mb-1">Report an Animal</h1>
    <p class="text-muted mb-4">
        Fill in the details below to report a stray, injured, or at-risk animal in your area.
        <strong>One photo is required</strong> and cannot be changed once the report is under review.
    </p>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="paw-card p-4">
                <form method="post"
                      action="<?= url('/actions/reports/report_submit.php') ?>"
                      enctype="multipart/form-data"
                      novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="animal_type" class="form-label fw-semibold">Animal Type <span class="text-danger">*</span></label>
                        <select name="animal_type" id="animal_type"
                                class="form-select" required
                                onchange="toggleSpeciesOther(this.value)">
                            <option value="">— Select —</option>
                            <option value="dog"   <?= old('animal_type') === 'dog'   ? 'selected' : '' ?>>Dog</option>
                            <option value="cat"   <?= old('animal_type') === 'cat'   ? 'selected' : '' ?>>Cat</option>
                            <option value="other" <?= old('animal_type') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="mb-3" id="species_other_wrap"
                         style="display:<?= old('animal_type') === 'other' ? 'block' : 'none' ?>;">
                        <label for="species_other" class="form-label fw-semibold">Specify Species</label>
                        <input type="text" name="species_other" id="species_other"
                               class="form-control" maxlength="80"
                               value="<?= e(old('species_other')) ?>"
                               placeholder="e.g. rabbit, bird…">
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Report Title <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="title" id="title"
                               class="form-control" maxlength="150"
                               value="<?= e(old('title')) ?>"
                               placeholder="Brief headline for your report">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description"
                                  class="form-control" rows="4" required
                                  placeholder="Describe the animal's condition, behaviour, and any other relevant details."><?= e(old('description')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="barangay_id" class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                        <select name="barangay_id" id="barangay_id" class="form-select" required>
                            <option value="">— Select barangay —</option>
                            <?php foreach ($barangays as $brgy): ?>
                            <option value="<?= e($brgy['id']) ?>"
                                <?= old('barangay_id') == $brgy['id'] ? 'selected' : '' ?>>
                                <?= e($brgy['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="location_address" class="form-label fw-semibold">Location / Address <span class="text-danger">*</span></label>
                        <input type="text" name="location_address" id="location_address"
                               class="form-control" maxlength="255" required
                               value="<?= e(old('location_address')) ?>"
                               placeholder="Street, purok, or recognisable address">
                    </div>

                    <div class="mb-3">
                        <label for="location_landmark" class="form-label fw-semibold">Landmark <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="location_landmark" id="location_landmark"
                               class="form-control" maxlength="255"
                               value="<?= e(old('location_landmark')) ?>"
                               placeholder="Near the school, behind the market…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Urgency Level <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="urgency" id="urgency_<?= e($val) ?>"
                                       value="<?= e($val) ?>"
                                       <?= old('urgency', 'medium') === $val ? 'checked' : '' ?>>
                                <label class="form-check-label" for="urgency_<?= e($val) ?>">
                                    <span class="badge urgency--<?= e($val) ?>"><?= e($label) ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label fw-semibold">Photo <span class="text-danger">*</span></label>
                        <input type="file" name="photo" id="photo"
                               class="form-control" accept="image/*"
                               data-preview="photo_preview" required>
                        <div class="form-text">JPEG, PNG, or WebP. Maximum 5 MB.
                            Once the report is under review the photo cannot be changed.</div>
                        <div class="mt-2">
                            <img id="photo_preview" src="" alt="Photo preview"
                                 class="img-thumbnail d-none" style="max-height:200px;">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-paw">
                            <i class="bi bi-send-fill"></i> Submit Report
                        </button>
                        <a href="<?= url('/reports/my_reports.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSpeciesOther(val) {
    document.getElementById('species_other_wrap').style.display = (val === 'other') ? 'block' : 'none';
}

document.getElementById('photo').addEventListener('change', function () {
    const preview = document.getElementById('photo_preview');
    const file    = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.classList.add('d-none');
    }
});
</script>

<?php
layout_footer();
