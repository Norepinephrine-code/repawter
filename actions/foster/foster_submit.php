<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_RESIDENT);

[$clean, $errors] = validate($_POST, [
    'preferred_animal_type' => 'required|in:dog,cat,other',
    'preferred_notes'       => 'nullable|max:500',
    'household_capacity'    => 'required|int|min:1',
    'has_yard'              => 'nullable|in:0,1',
    'household_notes'       => 'nullable|max:500',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash_errors($errors);
    redirect('/foster/apply.php');
}

FosterModel::create([
    'applicant_id'          => user_id(),
    'status'                => 'submitted',
    'preferred_animal_type' => $clean['preferred_animal_type'],
    'preferred_notes'       => $clean['preferred_notes'] ?? null,
    'household_capacity'    => (int)$clean['household_capacity'],
    'has_yard'              => isset($clean['has_yard']) && $clean['has_yard'] !== ''
                                ? (int)$clean['has_yard']
                                : null,
    'household_notes'       => $clean['household_notes'] ?? null,
]);

notify_role(
    ROLE_WELFARE,
    'foster',
    'New foster application',
    'A resident applied to foster.',
    '/admin/foster/'
);

flash('success', 'Your foster application has been submitted. We will review it and notify you.');
redirect('/foster/my_applications.php');
