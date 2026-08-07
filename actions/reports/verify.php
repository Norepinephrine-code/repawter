<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'report_id' => 'required|int',
    'action'    => 'required',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/reports/index.php');
}

$reportId = (int)$clean['report_id'];
$action   = $clean['action'];

$report = ReportModel::find($reportId);
if (!$report) {
    flash('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

$postedMet   = is_array($_POST['is_met']  ?? null) ? $_POST['is_met']  : [];
$postedNotes = is_array($_POST['note']    ?? null) ? $_POST['note']    : [];

$criteria = ReportModel::active_criteria();
$responses = [];
foreach ($criteria as $c) {
    $cid = (string)$c['id'];
    $responses[(int)$cid] = [
        'is_met' => isset($postedMet[$cid]) && $postedMet[$cid] == 1,
        'note'   => isset($postedNotes[$cid]) ? (string)$postedNotes[$cid] : '',
    ];
}

ReportModel::save_checklist($reportId, $responses, user_id());

if ($action === 'verify') {
    if (ReportModel::all_required_met($reportId)) {
        ReportModel::set_verified($reportId, user_id());
        notify(
            (int)$report['reporter_id'],
            'report',
            'Report verified',
            'Your report has been verified.',
            '/reports/view.php?id=' . $reportId
        );
        audit_log('report_verified', 'report', $reportId);
        flash('success', 'Report marked as verified.');
    } else {
        flash('error', 'All required criteria must be met before verifying.');
    }
} else {
    flash('success', 'Checklist saved.');
}

redirect('/admin/reports/view.php?id=' . $reportId);
