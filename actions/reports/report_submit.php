<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_RESIDENT);

[$clean, $errors] = validate($_POST, [
    'animal_type'      => 'required|in:dog,cat,other',
    'species_other'    => 'nullable',
    'title'            => 'nullable|max:150',
    'description'      => 'required',
    'barangay_id'      => 'required|int',
    'location_address' => 'required|max:255',
    'location_landmark'=> 'nullable|max:255',
    'urgency'          => 'required|in:low,medium,high,critical',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash_errors($errors);
    redirect('/reports/submit.php');
}

$upload = handle_upload($_FILES['photo'] ?? [], 'reports');
if (!$upload['ok']) {
    flash_old($_POST);
    flash('error', $upload['error'] ?? 'Photo upload failed.');
    redirect('/reports/submit.php');
}

$reportData = [
    'reporter_id'        => user_id(),
    'barangay_id'        => (int) $clean['barangay_id'],
    'animal_type'        => $clean['animal_type'],
    'species_other'      => $clean['species_other']     ?? null,
    'title'              => $clean['title']              ?? null,
    'description'        => $clean['description'],
    'location_address'   => $clean['location_address'],
    'location_landmark'  => $clean['location_landmark'] ?? null,
    'urgency'            => $clean['urgency'],
    'photo_path'         => $upload['path'],
    'photo_original_name'=> $_FILES['photo']['name'] ?? null,
];

$reportId = ReportModel::create($reportData);

$urgency = $clean['urgency'];
$message = 'A new ' . $urgency . ' report was submitted.';
notify_role(ROLE_OFFICIAL, 'report', 'New animal report', $message, '/admin/reports/');
notify_role(ROLE_WELFARE,  'report', 'New animal report', $message, '/admin/reports/');

flash('success', 'Your report has been submitted successfully. We will review it shortly.');
redirect('/reports/my_reports.php');
