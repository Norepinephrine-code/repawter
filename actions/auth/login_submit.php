<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();

[$clean, $errors] = validate($_POST, [
    'email'    => 'required|email',
    'password' => 'required',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please enter your email and password.');
    redirect('/auth/login.php');
}

$user = attempt_login($clean['email'], $_POST['password']);

if ($user === false) {
    flash_old(['email' => $clean['email']]);
    flash('error', 'Invalid credentials or your account is not permitted to log in.');
    redirect('/auth/login.php');
}

set_current_user($user);

// Redirect to intended URL if set, else role-based default
$intended = $_SESSION['intended_url'] ?? null;
unset($_SESSION['intended_url']);

if ($intended) {
    redirect($intended);
}

$role = $user['role'];
if (in_array($role, [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true)) {
    redirect('/admin/dashboard.php');
} else {
    redirect('/');
}
