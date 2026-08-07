<?php
require_once __DIR__ . '/../app/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/announcements/calendar.php');
}

$ann = AnnouncementModel::find($id);
if (!$ann || $ann['status'] !== 'published') {
    http_response_code(404);
    include APP_ROOT . '/404.php';
    exit;
}

$categoryColors = [
    'vaccination_drive'  => '#2A9D8F',
    'adoption_event'     => '#F4794E',
    'tnr_schedule'       => '#6C5CE7',
    'welfare_operation'  => '#0984E3',
    'general'            => '#636E72',
];

$categoryLabels = [
    'vaccination_drive'  => 'Vaccination Drive',
    'adoption_event'     => 'Adoption Event',
    'tnr_schedule'       => 'TNR Schedule',
    'welfare_operation'  => 'Welfare Operation',
    'general'            => 'General',
];

$color = $categoryColors[$ann['category']] ?? '#636E72';
$label = $categoryLabels[$ann['category']] ?? ucfirst(str_replace('_', ' ', $ann['category']));

layout_header(e($ann['title']), 'announcements');
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url('/announcements/calendar.php') ?>">Announcements</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= e(mb_substr($ann['title'], 0, 40)) ?><?= mb_strlen($ann['title']) > 40 ? '...' : '' ?>
                </li>
            </ol>
        </nav>

        <div class="card paw-card">
            <div class="card-body p-4">

                <span class="badge mb-3" style="background-color:<?= e($color) ?>; font-size:.85em;">
                    <?= e($label) ?>
                </span>

                <h1 class="paw-page-title h2 mb-3"><?= e($ann['title']) ?></h1>

                <div class="d-flex flex-wrap gap-3 text-muted small mb-4">
                    <span>
                        <i class="bi bi-person-fill"></i>
                        <?= e($ann['author_name']) ?>
                    </span>
                    <?php if ($ann['barangay_name']): ?>
                        <span>
                            <i class="bi bi-pin-map-fill"></i>
                            <?= e($ann['barangay_name']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($ann['published_at']): ?>
                        <span>
                            <i class="bi bi-clock"></i>
                            Published <?= e(date('M j, Y', strtotime($ann['published_at']))) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($ann['event_start']): ?>
                    <div class="alert alert-light border mb-4">
                        <div class="fw-semibold mb-1">
                            <i class="bi bi-calendar-event text-paw"></i> Event Date
                        </div>
                        <div>
                            <?php if ($ann['is_all_day']): ?>
                                <?= e(date('l, F j, Y', strtotime($ann['event_start']))) ?>
                                <?php if ($ann['event_end'] && $ann['event_end'] !== $ann['event_start']): ?>
                                    &ndash; <?= e(date('l, F j, Y', strtotime($ann['event_end']))) ?>
                                <?php endif; ?>
                                <span class="badge bg-secondary ms-1">All Day</span>
                            <?php else: ?>
                                <?= e(date('l, F j, Y \a\t g:i A', strtotime($ann['event_start']))) ?>
                                <?php if ($ann['event_end']): ?>
                                    &ndash; <?= e(date('g:i A', strtotime($ann['event_end']))) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($ann['location_text']): ?>
                            <div class="mt-1">
                                <i class="bi bi-geo-alt-fill text-paw"></i>
                                <?= e($ann['location_text']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($ann['location_text']): ?>
                    <div class="mb-4 text-muted small">
                        <i class="bi bi-geo-alt-fill"></i> <?= e($ann['location_text']) ?>
                    </div>
                <?php endif; ?>

                <div class="announcement-body lh-lg">
                    <?= nl2br(e($ann['body'])) ?>
                </div>

                <?php if (!empty($ann['facebook_url'])): ?>
                    <div class="mt-4 pt-4 border-top">
                        <h5 class="fw-semibold mb-3">
                            <i class="bi bi-facebook text-primary"></i> Related Facebook Post
                        </h5>
                        <a href="<?= e($ann['facebook_url']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-paw mb-3">
                            <i class="bi bi-box-arrow-up-right me-1"></i>
                            View related Facebook post
                        </a>
                        <div class="ratio" style="padding-bottom:0">
                            <div style="max-width:500px;">
                                <iframe
                                    src="https://www.facebook.com/plugins/post.php?href=<?= rawurlencode($ann['facebook_url']) ?>&width=500&show_text=true"
                                    width="500"
                                    height="400"
                                    style="border:none;overflow:hidden;max-width:100%"
                                    scrolling="no"
                                    frameborder="0"
                                    allowfullscreen="true"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                                </iframe>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="mt-3">
            <a href="<?= url('/announcements/calendar.php') ?>" class="btn btn-paw-outline">
                <i class="bi bi-arrow-left"></i> Back to Announcements
            </a>
        </div>

    </div>
</div>

<?php layout_footer(); ?>
