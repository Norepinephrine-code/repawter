<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();

[$clean, $errors] = validate($_POST, [
    'full_name'        => 'required|max:150',
    'email'            => 'required|email',
    'contact_number'   => 'nullable',
    'barangay_id'      => 'nullable|int',
    'password'         => 'required|min:8',
    'password_confirm' => 'required',
]);

// Password confirmation check
if (empty($errors['password']) && empty($errors['password_confirm'])) {
    if ($_POST['password'] !== $_POST['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
}

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors below.');
    redirect('/auth/register.php');
}

$result = register_resident($clean);

if (is_array($result) && isset($result['errors'])) {
    // Foundation returned validation errors (e.g. duplicate email)
    flash_old($_POST);
    foreach ($result['errors'] as $msg) {
        flash('error', $msg);
    }
    redirect('/auth/register.php');
}

// Auto-login
$newUser = UserModel::find((int)$result);
if ($newUser) {
    set_current_user($newUser);
}

flash('success', 'Welcome to RePawter! Your account has been created.');
redirect('/');
