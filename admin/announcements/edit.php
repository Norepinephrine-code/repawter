<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$id  = (int)($_GET['id'] ?? 0);
$ann = null;

if ($id > 0) {
    $ann = AnnouncementModel::find($id);
    if (!$ann) {
        flash('error', 'Announcement not found.');
        redirect('/admin/announcements/index.php');
    }
}

$barangays = db_all("SELECT id, name FROM barangays WHERE is_active = 1 ORDER BY name");

$categories = [
    'vaccination_drive'  => 'Vaccination Drive',
    'adoption_event'     => 'Adoption Event',
    'tnr_schedule'       => 'TNR Schedule',
    'welfare_operation'  => 'Welfare Operation',
    'general'            => 'General',
];

$statuses = [
    'draft'     => 'Draft',
    'scheduled' => 'Scheduled',
    'published' => 'Published',
];

$pageTitle = $ann ? 'Edit Announcement' : 'New Announcement';

layout_header($pageTitle, 'announcements');
admin_nav('announcements');
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= url('/admin/announcements/index.php') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="paw-page-title h3 mb-0"><?= e($pageTitle) ?></h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card paw-card">
            <div class="card-body p-4">

                <form method="post" action="<?= url('/actions/announcements/announcement_save.php') ?>">
                    <?= csrf_field() ?>
                    <?php if ($ann): ?>
                        <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="category" class="form-label fw-semibold">
                            Category <span class="text-danger">*</span>
                        </label>
                        <select id="category" name="category" class="form-select" required>
                            <?php foreach ($categories as $val => $lbl): ?>
                                <option value="<?= e($val) ?>"
                                    <?= old('category', $ann['category'] ?? 'general') === $val ? 'selected' : '' ?>>
                                    <?= e($lbl) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="title" name="title" class="form-control"
                               maxlength="200" required
                               value="<?= e(old('title', $ann['title'] ?? '')) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label fw-semibold">
                            Body <span class="text-danger">*</span>
                        </label>
                        <textarea id="body" name="body" class="form-control" rows="8" required><?= e(old('body', $ann['body'] ?? '')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="location_text" class="form-label fw-semibold">Location</label>
                        <input type="text" id="location_text" name="location_text" class="form-control"
                               value="<?= e(old('location_text', $ann['location_text'] ?? '')) ?>"
                               placeholder="e.g. Barangay Hall, San Jose Street">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="event_start" class="form-label fw-semibold">Event Start</label>
                            <input type="datetime-local" id="event_start" name="event_start"
                                   class="form-control"
                                   value="<?= e(old('event_start', isset($ann['event_start']) && $ann['event_start'] ? date('Y-m-d\TH:i', strtotime($ann['event_start'])) : '')) ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="event_end" class="form-label fw-semibold">Event End</label>
                            <input type="datetime-local" id="event_end" name="event_end"
                                   class="form-control"
                                   value="<?= e(old('event_end', isset($ann['event_end']) && $ann['event_end'] ? date('Y-m-d\TH:i', strtotime($ann['event_end'])) : '')) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="is_all_day"
                                       name="is_all_day" value="1"
                                    <?= old('is_all_day', (string)($ann['is_all_day'] ?? 0)) === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_all_day">All day</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="facebook_url" class="form-label fw-semibold">Facebook Post URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" class="form-control"
                               placeholder="https://www.facebook.com/..."
                               value="<?= e(old('facebook_url', $ann['facebook_url'] ?? '')) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" name="status" class="form-select" required>
                                <?php foreach ($statuses as $val => $lbl): ?>
                                    <option value="<?= e($val) ?>"
                                        <?= old('status', $ann['status'] ?? 'draft') === $val ? 'selected' : '' ?>>
                                        <?= e($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="publish_at" class="form-label fw-semibold">Schedule Publish At</label>
                            <input type="datetime-local" id="publish_at" name="publish_at"
                                   class="form-control"
                                   value="<?= e(old('publish_at', isset($ann['publish_at']) && $ann['publish_at'] ? date('Y-m-d\TH:i', strtotime($ann['publish_at'])) : '')) ?>">
                            <div class="form-text">Optional. Used when status is "Scheduled".</div>
                        </div>
                        <div class="col-md-4">
                            <label for="barangay_id" class="form-label fw-semibold">Barangay (optional)</label>
                            <select id="barangay_id" name="barangay_id" class="form-select">
                                <option value="">Platform-wide</option>
                                <?php foreach ($barangays as $brgy): ?>
                                    <option value="<?= (int)$brgy['id'] ?>"
                                        <?= (string)old('barangay_id', (string)($ann['barangay_id'] ?? '')) === (string)$brgy['id'] ? 'selected' : '' ?>>
                                        <?= e($brgy['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-2">
                        <a href="<?= url('/admin/announcements/index.php') ?>"
                           class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-paw">
                            <i class="bi bi-floppy me-1"></i>
                            <?= $ann ? 'Update Announcement' : 'Create Announcement' ?>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
