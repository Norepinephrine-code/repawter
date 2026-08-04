<?php
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/system/criteria.php');
}

csrf_verify();
require_role(ROLE_ADMIN);

$isUpdate = isset($_POST['id']) && (int)$_POST['id'] > 0;

$rules = [
    'label'       => 'required|max:200',
    'description' => 'nullable|max:500',
    'sort_order'  => 'nullable|int',
];
if (!$isUpdate) {
    $rules['code'] = 'required|max:50';
}

[$clean, $errors] = validate($_POST, $rules);

$isRequired = isset($_POST['is_required']) && $_POST['is_required'] === '1' ? 1 : 0;
$isActive   = isset($_POST['is_active'])   && $_POST['is_active']   === '1' ? 1 : 0;

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors: ' . implode(', ', $errors));
    redirect('/admin/system/criteria.php');
}

$label       = $clean['label'];
$description = $clean['description'] ?: null;
$sortOrder   = isset($clean['sort_order']) && $clean['sort_order'] !== '' ? (int)$clean['sort_order'] : 0;

if ($isUpdate) {
    $id = (int)$_POST['id'];

    $existing = db_one('SELECT id FROM verification_criteria WHERE id = ?', [$id]);
    if (!$existing) {
        flash('error', 'Criterion not found.');
        redirect('/admin/system/criteria.php');
    }

    db_exec(
        'UPDATE verification_criteria
         SET label = ?, description = ?, is_required = ?, is_active = ?, sort_order = ?, updated_at = NOW()
         WHERE id = ?',
        [$label, $description, $isRequired, $isActive, $sortOrder, $id]
    );

    audit_log('verification_criteria_changed', 'verification_criteria', $id);
    flash('success', 'Criterion updated successfully.');
} else {
    $code = trim($clean['code'] ?? '');

    $existing = db_one('SELECT id FROM verification_criteria WHERE code = ?', [$code]);
    if ($existing) {
        flash_old($_POST);
        flash('error', 'A criterion with that code already exists.');
        redirect('/admin/system/criteria.php');
    }

    db_exec(
        'INSERT INTO verification_criteria
            (code, label, description, is_required, is_active, sort_order, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
        [$code, $label, $description, $isRequired, $isActive, $sortOrder, user_id()]
    );
    $id = (int)db_insert_id();

    audit_log('verification_criteria_changed', 'verification_criteria', $id);
    flash('success', 'New criterion added successfully.');
}

redirect('/admin/system/criteria.php');
