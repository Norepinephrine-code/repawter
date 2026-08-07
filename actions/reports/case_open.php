<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'report_id' => 'required|int',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect('/admin/reports/index.php');
}

$reportId = (int)$clean['report_id'];

$report = ReportModel::find($reportId);
if (!$report) {
    flash('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

$existing = CaseModel::find_by_report($reportId);
if ($existing) {
    flash('info', 'A case already exists for this report.');
    redirect('/admin/reports/case_tracking.php?id=' . (int)$existing['id']);
}

$newCaseId = CaseModel::create_from_report($reportId, user_id());

$terminalStatuses = ['resolved', 'archived'];
if (!in_array($report['status'], $terminalStatuses, true)) {
    ReportModel::set_status($reportId, 'in_progress', user_id(), 'Case opened');
}

notify(
    (int)$report['reporter_id'],
    'report',
    'Case opened for your report',
    'A case has been opened for your report and is now being handled.',
    '/reports/view.php?id=' . $reportId
);

audit_log('case_opened', 'report', $reportId, ['case_id' => $newCaseId]);

flash('success', 'Case opened successfully.');
redirect('/admin/reports/case_tracking.php?id=' . $newCaseId);
