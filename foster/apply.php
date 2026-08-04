<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

require_role(ROLE_RESIDENT);

layout_header('Apply to Foster', 'foster');
?>

<div class="container py-4" style="max-width:700px;">
    <h1 class="paw-page-title mb-1">Foster Application</h1>
    <p class="text-muted mb-4">
        Applications are reviewed manually by our welfare team.
        You will receive a notification once a decision has been made.
    </p>

    <div class="card paw-card">
        <div class="card-body">
            <form method="post" action="<?= url('/actions/foster/foster_submit.php') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="preferred_animal_type" class="form-label fw-semibold">
                        Preferred Animal Type <span class="text-danger">*</span>
                    </label>
                    <select name="preferred_animal_type" id="preferred_animal_type"
                            class="form-select" required>
                        <?php foreach (['dog' => 'Dog', 'cat' => 'Cat', 'other' => 'Other'] as $val => $lbl): ?>
                        <option value="<?= e($val) ?>"
                            <?= old('preferred_animal_type', 'dog') === $val ? 'selected' : '' ?>>
                            <?= e($lbl) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="preferred_notes" class="form-label fw-semibold">
                        Preferences / Special Notes
                    </label>
                    <textarea name="preferred_notes" id="preferred_notes"
                              class="form-control" rows="3"
                              maxlength="500"
                              placeholder="E.g. prefers small dogs, no cats at home…"><?= e(old('preferred_notes')) ?></textarea>
                    <div class="form-text">Optional — max 500 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="household_capacity" class="form-label fw-semibold">
                        Household Capacity (max animals you can foster at once) <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="household_capacity" id="household_capacity"
                           class="form-control" min="1" required
                           value="<?= e(old('household_capacity', '1')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Do you have a yard / outdoor space?</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="has_yard" id="yard_yes" value="1"
                                   <?= old('has_yard') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="yard_yes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="has_yard" id="yard_no" value="0"
                                   <?= old('has_yard') === '0' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="yard_no">No</label>
                        </div>
                    </div>
                    <div class="form-text">Optional.</div>
                </div>

                <div class="mb-4">
                    <label for="household_notes" class="form-label fw-semibold">
                        About Your Household
                    </label>
                    <textarea name="household_notes" id="household_notes"
                              class="form-control" rows="3"
                              maxlength="500"
                              placeholder="Describe your living situation, other pets, family members, etc."><?= e(old('household_notes')) ?></textarea>
                    <div class="form-text">Optional — max 500 characters.</div>
                </div>

                <div class="alert alert-info small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Approvals are <strong>manual</strong>. Our welfare team will review your application and
                    notify you by site notification. This may take a few business days.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-paw">
                        <i class="bi bi-send me-1"></i> Submit Application
                    </button>
                    <a href="<?= url('/foster/my_applications.php') ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
