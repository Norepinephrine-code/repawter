<?php
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'report_id'   => 'required|int',
    'assignee_id' => 'required|int',
]);

if (!empty($errors)) {
    flash('error', 'Please select a staff member to assign.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reports/index.php');
}

$reportId  = (int)$clean['report_id'];
$assigneeId = (int)$clean['assignee_id'];

$report = ReportModel::find($reportId);
if (!$report) {
    flash('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

$assignee = db_one(
    "SELECT id, full_name, email FROM users WHERE id = ? AND role IN ('barangay_official','welfare_org') AND account_status = 'active'",
    [$assigneeId]
);
if (!$assignee) {
    flash('error', 'Invalid assignee selected.');
    redirect('/admin/reports/view.php?id=' . $reportId);
}

ReportModel::assign($reportId, $assigneeId, user_id());

notify(
    $assigneeId,
    'report',
    'Report assigned to you',
    'A report has been assigned to you: ' . ($report['title'] ?: 'Report #' . $reportId),
    '/admin/reports/view.php?id=' . $reportId
);
notify(
    (int)$report['reporter_id'],
    'report',
    'Report assigned',
    'Your report has been assigned to a staff member.',
    '/reports/view.php?id=' . $reportId
);

audit_log('report_assigned', 'report', $reportId);

flash('success', 'Report assigned to ' . $assignee['full_name'] . '.');
redirect('/admin/reports/view.php?id=' . $reportId);
