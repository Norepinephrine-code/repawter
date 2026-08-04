<?php
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_ADMIN, ROLE_OFFICIAL, ROLE_WELFARE);

$id  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$res = ($id !== null && $id > 0) ? ResourceModel::find($id) : null;

if ($id !== null && $res === null) {
    flash('error', 'Resource not found.');
    redirect('/admin/resources/index.php');
}

$categories = ResourceModel::categories();
$isNew      = ($res === null);
$pageTitle  = $isNew ? 'New Resource' : 'Edit Resource';

$old = peek_old();
$val = static function (string $key, mixed $fallback = '') use ($old, $res): mixed {
    if (isset($old[$key])) return $old[$key];
    if ($res !== null && array_key_exists($key, $res)) return $res[$key];
    return $fallback;
};

layout_header($pageTitle, 'resources');
admin_nav('resources');
?>
<div class="d-flex align-items-center mb-4">
    <a href="<?= url('/admin/resources/index.php') ?>" class="text-decoration-none text-muted me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="paw-page-title mb-0"><?= e($pageTitle) ?></h1>
</div>

<div class="row">
    <div class="col-lg-9">
        <form method="post"
              action="<?= url('/actions/resources/resource_save.php') ?>"
              enctype="multipart/form-data"
              class="paw-card card p-4 shadow-sm">
            <?= csrf_field() ?>
            <?php if (!$isNew): ?>
            <input type="hidden" name="id" value="<?= (int)$res['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="category_id" class="form-label fw-semibold">
                    Category <span class="text-danger">*</span>
                </label>
                <select name="category_id" id="category_id" class="form-select" required>
                    <option value="">— Select category —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"
                        <?= (string)$val('category_id') === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">
                    Title <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" id="title" class="form-control"
                       maxlength="200"
                       value="<?= e($val('title')) ?>"
                       required>
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label fw-semibold">Slug
                    <small class="text-muted">(leave blank to auto-generate from title)</small>
                </label>
                <input type="text" name="slug" id="slug" class="form-control"
                       maxlength="220"
                       value="<?= e($val('slug')) ?>"
                       placeholder="my-resource-slug">
            </div>

            <div class="mb-3">
                <label for="summary" class="form-label fw-semibold">Summary</label>
                <textarea name="summary" id="summary" class="form-control" rows="2"
                          maxlength="500"
                          placeholder="Brief description shown in listing cards..."><?= e($val('summary')) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="body" class="form-label fw-semibold">
                    Body <span class="text-danger">*</span>
                </label>
                <textarea name="body" id="body" class="form-control" rows="14"
                          required
                          placeholder="Full article content..."><?= e($val('body')) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="cover" class="form-label fw-semibold">Cover Image</label>
                <?php if (!$isNew && !empty($res['cover_image_path'])): ?>
                <div class="mb-2">
                    <img src="<?= e(upload_url($res['cover_image_path'])) ?>"
                         alt="Current cover"
                         class="img-thumbnail"
                         style="max-height:120px;">
                    <div class="text-muted small mt-1">Upload a new image to replace the current one.</div>
                </div>
                <?php endif; ?>
                <input type="file" name="cover" id="cover" class="form-control"
                       accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPEG, PNG, or WebP. Max 5 MB.</div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="is_published" id="is_published" value="1"
                        <?= $val('is_published', $isNew ? 0 : ($res['is_published'] ?? 0)) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="is_published">
                        Published (visible to the public)
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-paw">
                    <i class="bi bi-save me-1"></i><?= $isNew ? 'Create Resource' : 'Save Changes' ?>
                </button>
                <a href="<?= url('/admin/resources/index.php') ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="col-lg-3 mt-4 mt-lg-0">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i>Tips</h6>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Leave <strong>Slug</strong> blank to auto-generate from the title.</li>
                    <li>Use the <strong>Summary</strong> field for the card excerpt (max 500 chars).</li>
                    <li>The <strong>Body</strong> supports HTML formatting (e.g. <code>&lt;p&gt;</code>, <code>&lt;h2&gt;</code>, <code>&lt;strong&gt;</code>).</li>
                    <li>Check <strong>Published</strong> to make the resource visible publicly.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
layout_footer();
