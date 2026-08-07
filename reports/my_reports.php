<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_login();

$page   = max(1, (int) ($_GET['page'] ?? 1));
$result = ReportModel::list_by_user((int) user_id(), $page);
$rows   = $result['rows'];

layout_header('My Reports', 'my_reports');
?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="paw-page-title mb-0">My Reports</h1>
        <a href="<?= url('/reports/submit.php') ?>" class="btn btn-paw">
            <i class="bi bi-plus-circle"></i> New Report
        </a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="paw-card p-5 text-center text-muted">
            <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
            <p class="mb-3">You have not submitted any reports yet.</p>
            <a href="<?= url('/reports/submit.php') ?>" class="btn btn-paw">Report an Animal</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($rows as $r): ?>
            <div class="col-12">
                <div class="paw-card p-3 d-flex gap-3 align-items-start">

                    <?php if (!empty($r['photo_path'])): ?>
                    <img src="<?= e(photo_url($r['photo_path'] ?? null)) ?>"
                         alt="Report photo"
                         class="rounded"
                         style="width:90px;height:70px;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                    <div class="rounded bg-secondary d-flex align-items-center justify-content-center"
                         style="width:90px;height:70px;flex-shrink:0;">
                        <i class="bi bi-image text-white fs-3"></i>
                    </div>
                    <?php endif; ?>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <strong><?= e($r['title'] ?: ucfirst($r['animal_type']) . ' report') ?></strong>
                            <span class="badge urgency--<?= e($r['urgency']) ?>"><?= e(ucfirst($r['urgency'])) ?></span>
                            <?php partial('report_status_badge', ['status' => $r['status']]) ?>
                        </div>
                        <div class="text-muted small mb-1">
                            <i class="bi bi-geo-alt"></i> <?= e($r['location_address']) ?>
                            <?php if (!empty($r['barangay_name'])): ?>
                            &middot; <?= e($r['barangay_name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-calendar3"></i>
                            Submitted <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <a href="<?= url('/reports/view.php?id=' . (int) $r['id']) ?>"
                           class="btn btn-sm btn-paw-outline">
                            View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <?= render_pager($result, url('/reports/my_reports.php?page=%d')) ?>
        </div>
    <?php endif; ?>
</div>

<?php
layout_footer();
