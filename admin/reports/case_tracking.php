<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$caseId = (int)($_GET['id'] ?? 0);

if (!$caseId) {

    $filters = ['status' => $_GET['status'] ?? ''];
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $result  = CaseModel::list_filtered($filters, $page);
    $cases   = $result['rows'];

    $caseStatuses = ['open','triaged','rescued','under_care','ready_for_adoption','fostered','adopted','released','deceased','closed'];

    layout_header('Case Tracking', 'cases');
    admin_nav('cases');
    ?>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="paw-page-title mb-0">Case Tracking</h1>
            <span class="text-muted"><?= (int)$result['total'] ?> case(s)</span>
        </div>

        <form method="get" class="row g-2 mb-4 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($caseStatuses as $s): ?>
                    <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-paw btn-sm">Filter</button>
                <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Case
                        <th>Report</th>
                        <th>Status</th>
                        <th>Manager</th>
                        <th>Opened</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No cases found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($cases as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($c['case_number']) ?></td>
                        <td><?= e($c['report_title'] ?: '(Untitled)') ?></td>
                        <td><span class="badge bg-secondary"><?= e(ucfirst(str_replace('_',' ',$c['status']))) ?></span></td>
                        <td><?= e($c['manager_name'] ?? '—') ?></td>
                        <td><small><?= e(date('M d, Y', strtotime($c['opened_at']))) ?></small></td>
                        <td>
                            <a href="<?= url('/admin/reports/case_tracking.php?id=' . (int)$c['id']) ?>" class="btn btn-paw-teal btn-sm">Open</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= render_pager($result, '?' . http_build_query(array_merge(array_filter($filters), ['page' => '%d']))) ?>
    </div>
    <?php layout_footer(); ?>
    <?php
} else {

    $case = CaseModel::find($caseId);
    if (!$case) {
        flash('error', 'Case not found.');
        redirect('/admin/reports/case_tracking.php');
    }

    $updates  = CaseModel::updates_for($caseId);
    $pets     = db_all('SELECT id, name FROM pets ORDER BY name');

    $caseStatuses = ['open','triaged','rescued','under_care','ready_for_adoption','fostered','adopted','released','deceased','closed'];

    layout_header('Case ' . e($case['case_number']), 'cases');
    admin_nav('cases');
    ?>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4 gap-2">
            <a href="<?= url('/admin/reports/case_tracking.php') ?>" class="btn btn-outline-secondary btn-sm">← All Cases</a>
            <h1 class="paw-page-title mb-0">Case <?= e($case['case_number']) ?></h1>
            <span class="badge bg-secondary"><?= e(ucfirst(str_replace('_',' ',$case['status']))) ?></span>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">

                <div class="paw-card p-3 mb-3">
                    <h6 class="fw-semibold mb-3">Case Details</h6>
                    <dl class="row small mb-0">
                        <dt class="col-sm-5 text-muted">Case Number</dt>
                        <dd class="col-sm-7"><?= e($case['case_number']) ?></dd>

                        <dt class="col-sm-5 text-muted">Status</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary"><?= e(ucfirst(str_replace('_',' ',$case['status']))) ?></span></dd>

                        <dt class="col-sm-5 text-muted">Animal Type</dt>
                        <dd class="col-sm-7"><?= e(ucfirst($case['animal_type'])) ?></dd>

                        <?php if ($case['animal_name']): ?>
                        <dt class="col-sm-5 text-muted">Animal Name</dt>
                        <dd class="col-sm-7"><?= e($case['animal_name']) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-5 text-muted">Managed By</dt>
                        <dd class="col-sm-7"><?= e($case['manager_name'] ?? '—') ?></dd>

                        <dt class="col-sm-5 text-muted">Opened</dt>
                        <dd class="col-sm-7"><?= e(date('M d, Y g:i A', strtotime($case['opened_at']))) ?></dd>

                        <?php if ($case['closed_at']): ?>
                        <dt class="col-sm-5 text-muted">Closed</dt>
                        <dd class="col-sm-7"><?= e(date('M d, Y g:i A', strtotime($case['closed_at']))) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-5 text-muted">Linked Report</dt>
                        <dd class="col-sm-7">
                            <a href="<?= url('/admin/reports/view.php?id=' . (int)$case['report_id']) ?>">
                                <?= e($case['report_title'] ?: 'Report #' . $case['report_id']) ?>
                            </a>
                        </dd>

                        <?php if ($case['pet_profile_id']): ?>
                        <dt class="col-sm-5 text-muted">Pet Profile</dt>
                        <dd class="col-sm-7">
                            <a href="<?= url('/admin/pets/view.php?id=' . (int)$case['pet_profile_id']) ?>">
                                <?= e($case['pet_name'] ?? 'Pet #' . $case['pet_profile_id']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="paw-card p-3">
                    <h6 class="fw-semibold mb-3">Update Timeline</h6>
                    <?php if (empty($updates)): ?>
                    <p class="text-muted small">No updates yet.</p>
                    <?php else: ?>
                    <ol class="list-group list-group-flush small">
                        <?php foreach ($updates as $u): ?>
                        <li class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <?php if ($u['from_status'] || $u['to_status']): ?>
                                    <?php if ($u['from_status']): ?><span class="text-muted"><?= e(ucfirst(str_replace('_',' ',$u['from_status']))) ?></span> → <?php endif; ?>
                                    <?php if ($u['to_status']): ?><strong><?= e(ucfirst(str_replace('_',' ',$u['to_status']))) ?></strong><?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted fst-italic">Note</span>
                                    <?php endif; ?>
                                </span>
                                <span class="text-muted"><?= e(date('M d g:i A', strtotime($u['created_at']))) ?></span>
                            </div>
                            <?php if ($u['author_name']): ?>
                            <div class="text-muted">by <?= e($u['author_name']) ?></div>
                            <?php endif; ?>
                            <?php if ($u['note']): ?>
                            <div class="mt-1"><?= nl2br(e($u['note'])) ?></div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7">

                <div class="paw-card p-3 mb-3">
                    <h6 class="fw-semibold mb-3">Update Case Status</h6>
                    <form method="post" action="<?= url('/actions/reports/case_update.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="case_id" value="<?= e($caseId) ?>">
                        <input type="hidden" name="intent" value="update_status">
                        <div class="mb-2">
                            <select name="new_status" class="form-select form-select-sm" required>
                                <option value="">— Select status —</option>
                                <?php foreach ($caseStatuses as $s): ?>
                                <option value="<?= e($s) ?>"<?= $case['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Optional note…"></textarea>
                        </div>
                        <button type="submit" class="btn btn-paw btn-sm">Update Status</button>
                    </form>
                </div>

                <div class="paw-card p-3 mb-3">
                    <h6 class="fw-semibold mb-3">Add Note</h6>
                    <form method="post" action="<?= url('/actions/reports/case_update.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="case_id" value="<?= e($caseId) ?>">
                        <input type="hidden" name="intent" value="add_note">
                        <div class="mb-2">
                            <textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Enter a note…" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-paw-teal btn-sm">Add Note</button>
                    </form>
                </div>

                <div class="paw-card p-3">
                    <h6 class="fw-semibold mb-3">Link Pet Profile</h6>
                    <form method="post" action="<?= url('/actions/reports/case_update.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="case_id" value="<?= e($caseId) ?>">
                        <input type="hidden" name="intent" value="link_pet">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <select name="pet_id" class="form-select form-select-sm" required>
                                    <option value="">— Select pet —</option>
                                    <?php foreach ($pets as $p): ?>
                                    <option value="<?= e($p['id']) ?>"<?= (string)($case['pet_profile_id'] ?? '') === (string)$p['id'] ? ' selected' : '' ?>>
                                        <?= e($p['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-paw-outline btn-sm">Link Pet</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php layout_footer(); ?>
    <?php
}
?>
