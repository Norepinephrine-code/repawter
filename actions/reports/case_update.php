<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'case_id' => 'required|int',
    'intent'  => 'required',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect('/admin/reports/case_tracking.php');
}

$caseId = (int)$clean['case_id'];
$intent = $clean['intent'];

$case = CaseModel::find($caseId);
if (!$case) {
    flash('error', 'Case not found.');
    redirect('/admin/reports/case_tracking.php');
}

$validCaseStatuses = ['open','triaged','rescued','under_care','ready_for_adoption','fostered','adopted','released','deceased','closed'];

switch ($intent) {
    case 'update_status':
        [$inner, $innerErr] = validate($_POST, [
            'new_status' => 'required|in:' . implode(',', $validCaseStatuses),
        ]);
        if (!empty($innerErr)) {
            flash('error', 'Please select a valid case status.');
            redirect('/admin/reports/case_tracking.php?id=' . $caseId);
        }
        $note = trim($_POST['note'] ?? '') ?: null;
        CaseModel::update_status($caseId, $inner['new_status'], user_id(), $note);
        flash('success', 'Case status updated.');
        break;

    case 'add_note':
        $note = trim($_POST['note'] ?? '');
        if ($note === '') {
            flash('error', 'Note cannot be empty.');
            redirect('/admin/reports/case_tracking.php?id=' . $caseId);
        }
        CaseModel::add_update($caseId, $note, user_id());
        flash('success', 'Note added.');
        break;

    case 'link_pet':
        [$inner, $innerErr] = validate($_POST, [
            'pet_id' => 'required|int',
        ]);
        if (!empty($innerErr)) {
            flash('error', 'Please select a pet to link.');
            redirect('/admin/reports/case_tracking.php?id=' . $caseId);
        }
        CaseModel::link_pet($caseId, (int)$inner['pet_id']);
        flash('success', 'Pet profile linked to case.');
        break;

    default:
        flash('error', 'Unknown action.');
}

redirect('/admin/reports/case_tracking.php?id=' . $caseId);
