<?php
require __DIR__ . '/../app/bootstrap.php';

require_login();

$page   = max(1, (int)($_GET['page'] ?? 1));
$result = NotificationModel::list_for(user_id(), $page);
$rows   = $result['rows'];

// Icon map by notification type
$typeIcons = [
    'system'     => 'bi-gear-fill text-secondary',
    'report'     => 'bi-flag-fill text-warning',
    'case'       => 'bi-clipboard2-pulse text-info',
    'foster'     => 'bi-house-heart text-success',
    'adoption'   => 'bi-bag-heart text-paw',
    'pet'        => 'bi-heart-fill text-danger',
    'announcement' => 'bi-megaphone-fill text-primary',
];

function notif_icon(string $type, array $map): string
{
    return $map[$type] ?? 'bi-bell-fill text-secondary';
}

function relative_time(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)       return 'just now';
    if ($diff < 3600)     return floor($diff / 60) . 'm ago';
    if ($diff < 86400)    return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)   return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

layout_header('Notifications', 'notifications');
?>
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="paw-page-title mb-0">
                <i class="bi bi-bell-fill me-2"></i>Notifications
            </h1>
            <?php if (!empty($rows)): ?>
            <form method="post" action="<?= url('/actions/notifications/mark_all_read.php') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-paw-outline btn-sm">
                    <i class="bi bi-check-all me-1"></i>Mark all read
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($rows)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
            <p class="mb-0">You have no notifications yet.</p>
        </div>
        <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($rows as $n): ?>
            <?php $unread = ($n['read_at'] === null); ?>
            <div class="list-group-item list-group-item-action p-3<?= $unread ? ' bg-light border-start border-4 border-warning' : '' ?>">
                <div class="d-flex gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi <?= e(notif_icon($n['type'], $typeIcons)) ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="<?= $unread ? 'text-dark' : 'text-muted' ?>">
                                <?= e($n['title']) ?>
                                <?php if ($unread): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">New</span>
                                <?php endif; ?>
                            </strong>
                            <small class="text-muted ms-3 text-nowrap"><?= e(relative_time($n['created_at'])) ?></small>
                        </div>
                        <?php if (!empty($n['body'])): ?>
                        <p class="mb-2 text-muted small"><?= e($n['body']) ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (!empty($n['link_url'])): ?>
                            <form method="post" action="<?= url('/actions/notifications/mark_read.php') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id"       value="<?= (int)$n['id'] ?>">
                                <input type="hidden" name="redirect" value="<?= e($n['link_url']) ?>">
                                <button type="submit" class="btn btn-paw btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($unread): ?>
                            <form method="post" action="<?= url('/actions/notifications/mark_read.php') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id"       value="<?= (int)$n['id'] ?>">
                                <input type="hidden" name="redirect" value="/notifications/">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-check2 me-1"></i>Mark read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
        $pagerHtml = render_pager($result, url('/notifications/?page=%d'));
        if ($pagerHtml !== '') {
            echo '<div class="mt-4">' . $pagerHtml . '</div>';
        }
        ?>
        <?php endif; ?>
    </div>
</div>
<?php
layout_footer();
