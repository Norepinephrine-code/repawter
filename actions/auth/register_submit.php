<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

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

if (empty($errors['password']) && empty($errors['password_confirm'])) {
    if ($_POST['password'] !== $_POST['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
}

if (!empty($errors)) {
    flash_old($_POST);
    flash_errors($errors);
    redirect('/auth/register.php');
}

$result = register_resident($clean);

if (is_array($result) && isset($result['errors'])) {

    flash_old($_POST);
    foreach ($result['errors'] as $msg) {
        flash('error', $msg);
    }
    redirect('/auth/register.php');
}

$newUser = UserModel::find((int)$result);
if ($newUser) {
    login_as($newUser);
}

flash('success', 'Welcome to RePawter! Your account has been created.');
redirect('/');
