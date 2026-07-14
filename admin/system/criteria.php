<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_ADMIN);

$criteria = db_all(
    'SELECT * FROM verification_criteria ORDER BY sort_order ASC, id ASC'
);

layout_header('Verification Criteria', 'admin');
admin_nav('criteria');
?>

<div class="container-fluid py-4">
    <h1 class="paw-page-title mb-4">Verification Criteria</h1>

    <!-- Existing criteria -->
    <?php if (empty($criteria)): ?>
    <div class="alert alert-info">No criteria defined yet.</div>
    <?php else: ?>
    <div class="card paw-card mb-5">
        <div class="card-header fw-semibold">Existing Criteria</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">Order</th>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Description</th>
                            <th style="width:90px">Required</th>
                            <th style="width:80px">Active</th>
                            <th style="width:120px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($criteria as $c): ?>
                    <tr>
                        <form method="post" action="<?= url('/actions/system/criteria_save.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <td>
                                <input type="number" name="sort_order" class="form-control form-control-sm"
                                       value="<?= (int)$c['sort_order'] ?>" style="width:70px">
                            </td>
                            <td class="text-muted small"><?= e($c['code']) ?></td>
                            <td>
                                <input type="text" name="label" class="form-control form-control-sm"
                                       value="<?= e($c['label']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description" class="form-control form-control-sm"
                                       value="<?= e($c['description'] ?? '') ?>" placeholder="Optional description">
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_required" value="1"
                                           <?= $c['is_required'] ? 'checked' : '' ?>>
                                    <input type="hidden" name="_is_required_present" value="1">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           <?= $c['is_active'] ? 'checked' : '' ?>>
                                    <input type="hidden" name="_is_active_present" value="1">
                                </div>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-paw btn-sm">Save</button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add new criterion -->
    <div class="card paw-card">
        <div class="card-header fw-semibold">Add New Criterion</div>
        <div class="card-body">
            <form method="post" action="<?= url('/actions/system/criteria_save.php') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-2">
                    <label class="form-label small">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control form-control-sm"
                           placeholder="e.g. photo_clear" value="<?= e(old('code')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control form-control-sm"
                           placeholder="Label text" value="<?= e(old('label')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Description</label>
                    <input type="text" name="description" class="form-control form-control-sm"
                           placeholder="Optional description" value="<?= e(old('description')) ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Order</label>
                    <input type="number" name="sort_order" class="form-control form-control-sm"
                           value="<?= e(old('sort_order', '0')) ?>">
                </div>
                <div class="col-md-1 d-flex flex-column align-items-center">
                    <label class="form-label small">Required</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1"
                               <?= old('is_required') ? 'checked' : 'checked' ?>>
                        <input type="hidden" name="_is_required_present" value="1">
                    </div>
                </div>
                <div class="col-md-1 d-flex flex-column align-items-center">
                    <label class="form-label small">Active</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               <?= old('is_active', '1') ? 'checked' : '' ?>>
                        <input type="hidden" name="_is_active_present" value="1">
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-paw">Add Criterion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
