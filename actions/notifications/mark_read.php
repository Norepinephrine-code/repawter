<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/notifications/');
}

csrf_verify();
require_login();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    mark_read($id, user_id());
}

$target = $_POST['redirect'] ?? '';
if ($target !== '' && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
    redirect($target);
} else {
    redirect('/notifications/');
}
