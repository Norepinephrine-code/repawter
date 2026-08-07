<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_WELFARE, ROLE_ADMIN);

$isEdit = !empty($_POST['id']);
$petId  = $isEdit ? (int)$_POST['id'] : 0;

[$clean, $errors] = validate($_POST, [
    'name'              => 'required|max:80',
    'animal_type'       => 'required|in:dog,cat,other',
    'species_other'     => 'nullable|max:80',
    'breed'             => 'nullable|max:80',
    'sex'               => 'required|in:male,female,unknown',
    'approx_age_months' => 'nullable|int|min:0',
    'size'              => 'nullable|in:small,medium,large',
    'color'             => 'nullable|max:80',
    'is_vaccinated'     => 'nullable|in:0,1',
    'is_neutered'       => 'nullable|in:0,1',
    'description'       => 'nullable|max:5000',
    'status'            => 'required|in:draft,available,pending_adoption,adopted,archived',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash_errors($errors);
    redirect($isEdit ? '/admin/pets/edit.php?id=' . $petId : '/admin/pets/edit.php');
}

$photoPath    = null;
$existingPath = null;

if ($isEdit) {
    $existing = PetModel::find($petId);
    if ($existing === null) {
        flash('error', 'Pet not found.');
        redirect('/admin/pets/index.php');
    }
    $existingPath = $existing['photo_path'];
}

$hasUpload = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

if ($hasUpload) {
    $upload = handle_upload($_FILES['photo'], 'pets');
    if (!$upload['ok']) {
        flash_old($_POST);
        flash('error', 'Photo upload failed: ' . $upload['error']);
        redirect($isEdit ? '/admin/pets/edit.php?id=' . $petId : '/admin/pets/edit.php');
    }
    $photoPath = $upload['path'];
} elseif (!$isEdit) {

    flash_old($_POST);
    flash('error', 'A photo is required for a new pet.');
    redirect('/admin/pets/edit.php');
} else {

    $photoPath = $existingPath;
}

$ageMonths    = (isset($clean['approx_age_months']) && $clean['approx_age_months'] !== '')
    ? (int)$clean['approx_age_months']
    : null;
$isVaccinated = (isset($clean['is_vaccinated']) && $clean['is_vaccinated'] !== '')
    ? (int)$clean['is_vaccinated']
    : null;
$isNeutered   = (isset($clean['is_neutered']) && $clean['is_neutered'] !== '')
    ? (int)$clean['is_neutered']
    : null;

$data = [
    'name'              => $clean['name'],
    'animal_type'       => $clean['animal_type'],
    'species_other'     => ($clean['species_other'] ?? '') !== '' ? $clean['species_other'] : null,
    'breed'             => ($clean['breed'] ?? '') !== '' ? $clean['breed'] : null,
    'sex'               => $clean['sex'],
    'approx_age_months' => $ageMonths,
    'size'              => ($clean['size'] ?? '') !== '' ? $clean['size'] : null,
    'color'             => ($clean['color'] ?? '') !== '' ? $clean['color'] : null,
    'is_vaccinated'     => $isVaccinated,
    'is_neutered'       => $isNeutered,
    'description'       => ($clean['description'] ?? '') !== '' ? $clean['description'] : null,
    'photo_path'        => $photoPath,
    'status'            => $clean['status'],
];

if ($isEdit) {
    PetModel::update($petId, $data);

    if ($hasUpload && $existingPath !== null && $existingPath !== $photoPath) {
        delete_upload($existingPath);
    }
    flash('success', 'Pet profile updated successfully.');
} else {
    $data['created_by'] = (int)user_id();
    PetModel::create($data);
    flash('success', 'New pet added successfully.');
}

redirect('/admin/pets/index.php');
