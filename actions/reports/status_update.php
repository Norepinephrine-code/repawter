<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$validStatuses = ['submitted','under_review','verified','rejected','assigned','in_progress','resolved','archived'];

[$clean, $errors] = validate($_POST, [
    'report_id'  => 'required|int',
    'new_status' => 'required|in:' . implode(',', $validStatuses),
]);

if (!empty($errors)) {
    flash('error', 'Please select a valid status.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reports/index.php');
}

$reportId  = (int)$clean['report_id'];
$newStatus = $clean['new_status'];
$note      = trim($_POST['note'] ?? '') ?: null;

$report = ReportModel::find($reportId);
if (!$report) {
    flash('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

if ($newStatus === 'rejected') {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason !== '') {
        db_exec(
            'UPDATE reports SET rejection_reason = ?, updated_at = NOW() WHERE id = ?',
            [$reason, $reportId]
        );
    }
}

ReportModel::set_status($reportId, $newStatus, user_id(), $note);

notify(
    (int)$report['reporter_id'],
    'report',
    'Report status updated',
    'Your report is now: ' . ucfirst(str_replace('_', ' ', $newStatus)),
    '/reports/view.php?id=' . $reportId
);

audit_log('report_status_changed', 'report', $reportId, ['new_status' => $newStatus]);

flash('success', 'Status updated to "' . ucfirst(str_replace('_', ' ', $newStatus)) . '".');
redirect('/admin/reports/view.php?id=' . $reportId);
