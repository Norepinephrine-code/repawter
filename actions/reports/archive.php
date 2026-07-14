<?php
require __DIR__ . '/../../app/bootstrap.php';

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

ReportModel::archive($reportId, user_id());
audit_log('report_archived', 'report', $reportId);

flash('success', 'Report archived.');
redirect('/admin/reports/index.php');
