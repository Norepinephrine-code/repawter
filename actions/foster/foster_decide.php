<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_WELFARE, ROLE_OFFICIAL, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'id'            => 'required|int|min:1',
    'decision'      => 'required|in:approve,reject',
    'decision_notes'=> 'nullable|max:500',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect('/admin/foster/index.php');
}

$id  = (int)$clean['id'];
$app = FosterModel::find($id);

if ($app === null) {
    flash('error', 'Foster application not found.');
    redirect('/admin/foster/index.php');
}

$status      = $clean['decision'] === 'approve' ? 'approved' : 'rejected';
$notes       = $clean['decision_notes'] ?? null;
$reviewerId  = (int)user_id();
$applicantId = (int)$app['applicant_id'];

FosterModel::decide($id, $status, $reviewerId, $notes);

notify(
    $applicantId,
    'foster',
    'Foster application ' . $status,
    'Your foster application was ' . $status . '.',
    '/foster/my_applications.php'
);

audit_log('foster_decided', 'foster_application', $id);

flash('success', 'Application ' . $status . ' successfully.');
redirect('/admin/foster/view.php?id=' . $id);
