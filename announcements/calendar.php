<?php
require __DIR__ . '/../app/bootstrap.php';

$page      = max(1, (int)($_GET['page'] ?? 1));
$paginated = AnnouncementModel::list_published($page);
$rows      = $paginated['rows'];

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

layout_header('Announcements', 'announcements');
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="paw-page-title">Announcements &amp; Events</h1>
        <p class="text-muted">Stay up to date with vaccination drives, adoption events, TNR schedules, and more.</p>
    </div>
</div>

<div class="card paw-card mb-4">
    <div class="card-body p-3">
        <div id="calendar"
             data-feed="<?= e(url('/announcements/feed.php')) ?>">
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 mb-4">
<?php foreach ($categoryColors as $key => $color): ?>
    <span class="d-flex align-items-center gap-1 small">
        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:<?= e($color) ?>"></span>
        <?= e($categoryLabels[$key]) ?>
    </span>
<?php endforeach; ?>
</div>

<h2 class="h5 fw-bold mb-3 text-paw-dark">Upcoming &amp; Recent Announcements</h2>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">No announcements yet. Check back soon!</div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($rows as $ann): ?>
        <?php
        $color = $categoryColors[$ann['category']] ?? '#636E72';
        $label = $categoryLabels[$ann['category']] ?? ucfirst(str_replace('_', ' ', $ann['category']));
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card paw-card h-100">
                <div class="card-body">
                    <span class="badge mb-2" style="background-color:<?= e($color) ?>">
                        <?= e($label) ?>
                    </span>
                    <?php if ($ann['event_start']): ?>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-calendar-event"></i>
                            <?= e(date('M j, Y', strtotime($ann['event_start']))) ?>
                            <?php if (!$ann['is_all_day']): ?>
                                at <?= e(date('g:i A', strtotime($ann['event_start']))) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($ann['location_text']): ?>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-geo-alt"></i> <?= e($ann['location_text']) ?>
                        </div>
                    <?php endif; ?>
                    <h6 class="card-title mt-1 mb-2">
                        <a href="<?= e(url('/announcements/view.php?id=' . $ann['id'])) ?>"
                           class="text-decoration-none text-dark stretched-link">
                            <?= e($ann['title']) ?>
                        </a>
                    </h6>
                    <p class="card-text small text-muted mb-0">
                        <?= e(mb_substr(strip_tags($ann['body']), 0, 100)) ?>...
                    </p>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    <i class="bi bi-person"></i> <?= e($ann['author_name']) ?>
                    <?php if ($ann['barangay_name']): ?>
                        &bull; <?= e($ann['barangay_name']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($paginated['pages'] > 1): ?>
        <div class="mt-4">
            <?= render_pager($paginated, url('/announcements/calendar.php?page=%d')) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php layout_footer([asset('/js/calendar-init.js')]); ?>
