<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_login();

[$clean, $errors] = validate($_POST, [
    'current_password'  => 'required',
    'new_password'      => 'required|min:8',
    'new_password_confirm' => 'required',
]);

if (empty($errors['new_password']) && empty($errors['new_password_confirm'])) {
    if ($_POST['new_password'] !== $_POST['new_password_confirm']) {
        $errors['new_password_confirm'] = 'New passwords do not match.';
    }
}

if (!empty($errors)) {
    flash_errors($errors);
    redirect('/auth/profile.php');
}

$uid  = user_id();
$user = db_one('SELECT password_hash FROM users WHERE id = ? LIMIT 1', [$uid]);

if (!$user || !password_verify($_POST['current_password'], $user['password_hash'])) {
    flash('error', 'Your current password is incorrect.');
    redirect('/auth/profile.php');
}

UserModel::update_password($uid, hash_password($_POST['new_password']));

flash('success', 'Password updated successfully.');
redirect('/auth/profile.php');
