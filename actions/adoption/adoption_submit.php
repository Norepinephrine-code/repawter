<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_login();

[$clean, $errors] = validate($_POST, [
    'pet_id'        => 'required|int|min:1',
    'message'       => 'required|max:2000',
    'home_type'     => 'nullable|max:120',
    'has_other_pets'=> 'nullable|in:0,1',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors below.');
    $petId = (int)($_POST['pet_id'] ?? 0);
    redirect('/adoption/apply.php?pet_id=' . $petId);
}

$petId = (int)$clean['pet_id'];
$pet   = PetModel::find($petId);

if ($pet === null || $pet['status'] !== 'available') {
    flash('error', 'This pet is not available for adoption.');
    redirect('/adoption/gallery.php');
}

AdoptionModel::create([
    'pet_id'        => $petId,
    'applicant_id'  => user_id(),
    'status'        => 'submitted',
    'message'       => $clean['message'],
    'home_type'     => $clean['home_type'] ?? null,
    'has_other_pets'=> isset($clean['has_other_pets']) && $clean['has_other_pets'] !== ''
                        ? (int)$clean['has_other_pets']
                        : null,
]);

notify_role(
    ROLE_WELFARE,
    'adoption',
    'New Adoption Application',
    'Someone applied to adopt ' . $pet['name'] . '.',
    '/admin/adoption/'
);

flash('success', 'Your adoption application has been submitted! We will review it and get back to you soon.');
redirect('/adoption/pet.php?id=' . $petId);
