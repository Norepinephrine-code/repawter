<?php
declare(strict_types=1);

function hash_password(string $p): string
{
    return password_hash($p, PASSWORD_DEFAULT);
}

function attempt_login(string $email, string $pw): array|false
{
    $user = db_one(
        'SELECT * FROM users WHERE email = ? LIMIT 1',
        [strtolower(trim($email))]
    );

    if ($user === null) {
        return false;
    }

    if (!password_verify($pw, $user['password_hash'])) {
        return false;
    }

    if ($user['account_status'] === 'suspended') {
        return false;
    }

    db_exec(
        'UPDATE users SET last_login_at = NOW() WHERE id = ?',
        [$user['id']]
    );

    return $user;
}

function register_resident(array $d): int|array
{
    $errors = [];

    $email     = strtolower(trim($d['email'] ?? ''));
    $password  = $d['password'] ?? '';
    $full_name = trim($d['full_name'] ?? '');

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Must be a valid email address.';
    } else {
        $existing = db_one('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($existing !== null) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if (!empty($errors)) {
        return ['errors' => $errors];
    }

    db_exec(
        'INSERT INTO users (role, account_status, email, password_hash, full_name, contact_number, barangay_id, created_at)
         VALUES (\'community_resident\', \'active\', ?, ?, ?, ?, ?, NOW())',
        [
            $email,
            hash_password($password),
            $full_name,
            !empty($d['contact_number']) ? trim($d['contact_number']) : null,
            !empty($d['barangay_id'])    ? (int)$d['barangay_id']     : null,
        ]
    );

    return (int)db_insert_id();
}
