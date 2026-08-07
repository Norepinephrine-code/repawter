<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/resources/index.php');
}

csrf_verify();
require_role(ROLE_ADMIN, ROLE_OFFICIAL, ROLE_WELFARE);

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    ResourceModel::set_published($id, false);
    flash('success', 'Resource unpublished.');
} else {
    flash('error', 'Invalid resource.');
}

redirect('/admin/resources/index.php');
