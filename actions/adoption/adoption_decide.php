<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'id'            => 'required|int|min:1',
    'decision'      => 'required|in:approve,reject,complete',
    'decision_notes'=> 'nullable|max:500',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect('/admin/adoption/index.php');
}

$id    = (int)$clean['id'];
$app   = AdoptionModel::find($id);

if ($app === null) {
    flash('error', 'Application not found.');
    redirect('/admin/adoption/index.php');
}

$notes     = $clean['decision_notes'] ?? null;
$reviewer  = (int)user_id();
$applicantId = (int)$app['applicant_id'];

switch ($clean['decision']) {
    case 'approve':
        AdoptionModel::decide($id, 'approved', $reviewer, $notes);
        // Optionally mark pet pending_adoption
        PetModel::set_status((int)$app['pet_id'], 'pending_adoption');
        notify(
            $applicantId,
            'adoption',
            'Adoption Application Approved',
            'Congratulations! Your adoption application has been approved. Please visit the shelter to complete the process.',
            '/admin/adoption/view.php?id=' . $id
        );
        flash('success', 'Application approved.');
        break;

    case 'reject':
        AdoptionModel::decide($id, 'rejected', $reviewer, $notes);
        notify(
            $applicantId,
            'adoption',
            'Adoption Application Update',
            'We regret to inform you that your adoption application has not been approved at this time.' .
                ($notes ? ' Note: ' . $notes : ''),
            '/admin/adoption/view.php?id=' . $id
        );
        flash('success', 'Application rejected.');
        break;

    case 'complete':
        AdoptionModel::complete($id, $reviewer);
        notify(
            $applicantId,
            'adoption',
            'Adoption Completed',
            'Your adoption process has been marked as completed. Welcome to your new family member!',
            '/admin/adoption/view.php?id=' . $id
        );
        flash('success', 'Adoption marked as completed.');
        break;
}

redirect('/admin/adoption/view.php?id=' . $id);
