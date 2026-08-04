<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_login();

[$clean, $errors] = validate($_POST, [
    'full_name'      => 'required|max:150',
    'contact_number' => 'nullable',
    'barangay_id'    => 'nullable|int',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors below.');
    redirect('/auth/profile.php');
}

$uid = user_id();

UserModel::update_profile($uid, [
    'full_name'      => $clean['full_name'],
    'contact_number' => $clean['contact_number'],
    'barangay_id'    => $clean['barangay_id'],
]);

$updated = UserModel::find($uid);
if ($updated) {
    set_current_user($updated);
}

flash('success', 'Profile updated successfully.');
redirect('/auth/profile.php');
