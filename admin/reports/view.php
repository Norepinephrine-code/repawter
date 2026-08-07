<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect('/admin/reports/index.php');
}

$report = ReportModel::find_with_details($id);
if (!$report) {
    flash('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

$history   = ReportModel::status_history($id);
$checklist = ReportModel::checklist_for($id);
$allMet    = ReportModel::all_required_met($id);
$case      = CaseModel::find_by_report($id);

$staffList = db_all(
    "SELECT id, full_name, role FROM users
     WHERE role IN ('barangay_official','welfare_org') AND account_status = 'active'
     ORDER BY full_name"
);

$reportStatuses = ['submitted','under_review','verified','rejected','assigned','in_progress','resolved','archived'];

layout_header('Report #' . $id . ' — ' . e($report['title'] ?? 'Untitled'), 'reports');
admin_nav('reports');
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4 gap-2">
        <a href="<?= url('/admin/reports/index.php') ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
        <h1 class="paw-page-title mb-0">Report
        <?php partial('report_status_badge', ['status' => $report['status']]) ?>
        <span class="badge urgency--<?= e($report['urgency']) ?>"><?= e(ucfirst($report['urgency'])) ?></span>
    </div>

    <div class="row g-4">

        <div class="col-lg-5">
            <div class="paw-card p-3 mb-3">
                <?php if (!empty($report['photo_path'])): ?>
                <img src="<?= e(photo_url($report['photo_path'] ?? null)) ?>"
                     alt="Report photo"
                     class="report-photo w-100 rounded mb-3">
                <?php endif; ?>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Title</dt>
                    <dd class="col-sm-8"><?= e($report['title'] ?: '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Animal</dt>
                    <dd class="col-sm-8"><?= e(ucfirst($report['animal_type'])) ?><?= !empty($report['species_other']) ? ' — ' . e($report['species_other']) : '' ?></dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8"><?= e($report['location_address']) ?><?= !empty($report['location_landmark']) ? ' (' . e($report['location_landmark']) . ')' : '' ?></dd>

                    <dt class="col-sm-4 text-muted">Barangay</dt>
                    <dd class="col-sm-8"><?= e($report['barangay_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Reporter</dt>
                    <dd class="col-sm-8"><?= e($report['reporter_name']) ?> <small class="text-muted">(<?= e($report['reporter_email']) ?>)</small></dd>

                    <dt class="col-sm-4 text-muted">Contact</dt>
                    <dd class="col-sm-8"><?= e($report['reporter_contact'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Submitted</dt>
                    <dd class="col-sm-8"><?= e(date('M d, Y g:i A', strtotime($report['created_at']))) ?></dd>

                    <?php if ($report['verified_by']): ?>
                    <dt class="col-sm-4 text-muted">Verified by</dt>
                    <dd class="col-sm-8"><?= e($report['verified_by_name']) ?> <small class="text-muted"><?= e(date('M d, Y', strtotime($report['verified_at']))) ?></small></dd>
                    <?php endif; ?>

                    <?php if ($report['assigned_to']): ?>
                    <dt class="col-sm-4 text-muted">Assigned to</dt>
                    <dd class="col-sm-8"><?= e($report['assigned_to_name']) ?></dd>
                    <?php endif; ?>

                    <?php if ($report['rejection_reason']): ?>
                    <dt class="col-sm-4 text-muted">Rejection reason</dt>
                    <dd class="col-sm-8 text-danger"><?= e($report['rejection_reason']) ?></dd>
                    <?php endif; ?>

                    <?php if ($report['is_locked']): ?>
                    <dt class="col-sm-4 text-muted">Locked</dt>
                    <dd class="col-sm-8"><span class="badge bg-warning text-dark">Yes</span></dd>
                    <?php endif; ?>
                </dl>

                <div class="mt-2">
                    <h6 class="text-muted small text-uppercase">Description</h6>
                    <p class="mb-0"><?= nl2br(e($report['description'])) ?></p>
                </div>
            </div>

            <div class="paw-card p-3">
                <h6 class="fw-semibold mb-3">Status Timeline</h6>
                <?php if (empty($history)): ?>
                <p class="text-muted small">No history yet.</p>
                <?php else: ?>
                <ol class="list-group list-group-flush small">
                    <?php foreach ($history as $h): ?>
                    <li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <span>
                                <?php if ($h['from_status']): ?>
                                <span class="text-muted"><?= e(ucfirst(str_replace('_',' ',$h['from_status']))) ?></span>
                                →
                                <?php endif; ?>
                                <strong><?= e(ucfirst(str_replace('_',' ',$h['to_status']))) ?></strong>
                            </span>
                            <span class="text-muted"><?= e(date('M d g:i A', strtotime($h['created_at']))) ?></span>
                        </div>
                        <?php if ($h['changer_name']): ?>
                        <div class="text-muted">by <?= e($h['changer_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($h['note']): ?>
                        <div class="text-muted fst-italic"><?= e($h['note']) ?></div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">

            <div class="paw-card p-3 mb-3">
                <h6 class="fw-semibold mb-3">Verification Checklist
                    <?php if ($allMet): ?>
                    <span class="badge bg-success ms-2">All Required Met</span>
                    <?php else: ?>
                    <span class="badge bg-secondary ms-2">Incomplete</span>
                    <?php endif; ?>
                </h6>
                <form method="post" action="<?= url('/actions/reports/verify.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="report_id" value="<?= e($id) ?>">

                    <?php if (empty($checklist)): ?>
                    <p class="text-muted small">No active verification criteria configured.</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush mb-3">
                        <?php foreach ($checklist as $c): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex align-items-start gap-2">
                                <div class="form-check mt-1">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           name="is_met[<?= e($c['criteria_id']) ?>]"
                                           id="crit_<?= e($c['criteria_id']) ?>"
                                           value="1"
                                           <?= $c['is_met'] ? 'checked' : '' ?>>
                                </div>
                                <label class="form-check-label flex-grow-1" for="crit_<?= e($c['criteria_id']) ?>">
                                    <span class="fw-semibold"><?= e($c['label']) ?></span>
                                    <?php if ($c['is_required']): ?>
                                    <span class="badge bg-danger ms-1 small">Required</span>
                                    <?php endif; ?>
                                    <?php if ($c['description']): ?>
                                    <div class="text-muted small"><?= e($c['description']) ?></div>
                                    <?php endif; ?>
                                    <input type="text"
                                           name="note[<?= e($c['criteria_id']) ?>]"
                                           class="form-control form-control-sm mt-1"
                                           placeholder="Note (optional)"
                                           value="<?= e($c['note'] ?? '') ?>">
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="save" class="btn btn-paw-outline btn-sm">Save Checklist</button>
                        <button type="submit" name="action" value="verify" class="btn btn-paw btn-sm"
                                <?= !$allMet ? 'title="All required criteria must be met first"' : '' ?>>
                            Mark Verified
                        </button>
                    </div>
                </form>
            </div>

            <div class="paw-card p-3 mb-3">
                <h6 class="fw-semibold mb-3">Assign Report</h6>
                <form method="post" action="<?= url('/actions/reports/assign.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="report_id" value="<?= e($id) ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <select name="assignee_id" class="form-select form-select-sm" required>
                                <option value="">— Select staff member —</option>
                                <?php foreach ($staffList as $s): ?>
                                <option value="<?= e($s['id']) ?>"
                                    <?= (string)($report['assigned_to'] ?? '') === (string)$s['id'] ? ' selected' : '' ?>>
                                    <?= e($s['full_name']) ?>
                                    (<?= e(str_replace('_',' ',ucfirst($s['role']))) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-paw-teal btn-sm">Assign</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="paw-card p-3 mb-3">
                <h6 class="fw-semibold mb-3">Update Status</h6>
                <form method="post" action="<?= url('/actions/reports/status_update.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="report_id" value="<?= e($id) ?>">
                    <div class="mb-2">
                        <select name="new_status" class="form-select form-select-sm" id="statusSelect" required>
                            <option value="">— Select status —</option>
                            <?php foreach ($reportStatuses as $s): ?>
                            <option value="<?= e($s) ?>"<?= $report['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2" id="rejectionRow" style="display:none;">
                        <input type="text" name="rejection_reason" class="form-control form-control-sm"
                               placeholder="Rejection reason (required for rejected status)"
                               value="<?= e($report['rejection_reason'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Optional note…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-paw btn-sm">Update Status</button>
                </form>
                <script>
                document.getElementById('statusSelect').addEventListener('change', function() {
                    document.getElementById('rejectionRow').style.display = this.value === 'rejected' ? '' : 'none';
                });

                if (document.getElementById('statusSelect').value === 'rejected') {
                    document.getElementById('rejectionRow').style.display = '';
                }
                </script>
            </div>

            <div class="paw-card p-3 mb-3">
                <h6 class="fw-semibold mb-3">Archive Report</h6>
                <form method="post" action="<?= url('/actions/reports/archive.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="report_id" value="<?= e($id) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            data-confirm="Archive this report? This cannot be easily undone.">
                        Archive Report
                    </button>
                </form>
            </div>

            <div class="paw-card p-3">
                <h6 class="fw-semibold mb-3">Linked Case</h6>
                <?php if ($case): ?>
                <dl class="row small mb-2">
                    <dt class="col-sm-4 text-muted">Case
                    <dd class="col-sm-8"><?= e($case['case_number']) ?></dd>
                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8"><span class="badge bg-secondary"><?= e(ucfirst(str_replace('_',' ',$case['status']))) ?></span></dd>
                    <dt class="col-sm-4 text-muted">Manager</dt>
                    <dd class="col-sm-8"><?= e($case['manager_name'] ?? '—') ?></dd>
                </dl>
                <a href="<?= url('/admin/reports/case_tracking.php?id=' . (int)$case['id']) ?>"
                   class="btn btn-paw-teal btn-sm">View Case</a>
                <?php else: ?>
                <p class="text-muted small">No case opened for this report yet.</p>
                <form method="post" action="<?= url('/actions/reports/case_open.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="report_id" value="<?= e($id) ?>">
                    <button type="submit" class="btn btn-paw btn-sm">Open Case</button>
                </form>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php layout_footer(); ?>
