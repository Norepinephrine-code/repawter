<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_WELFARE, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'id' => 'required|int|min:1',
]);

if (!empty($errors)) {
    flash('error', 'Invalid request.');
    redirect('/admin/pets/index.php');
}

$id  = (int)$clean['id'];
$pet = PetModel::find($id);

if ($pet === null) {
    flash('error', 'Pet not found.');
    redirect('/admin/pets/index.php');
}

PetModel::set_status($id, 'archived');

flash('success', '"' . $pet['name'] . '" has been archived.');
redirect('/admin/pets/index.php');
