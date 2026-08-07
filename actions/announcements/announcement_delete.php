<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Invalid announcement ID.');
    redirect('/admin/announcements/index.php');
}

$ann = AnnouncementModel::find($id);
if (!$ann) {
    flash('error', 'Announcement not found.');
    redirect('/admin/announcements/index.php');
}

AnnouncementModel::archive($id);

flash('success', 'Announcement archived.');
redirect('/admin/announcements/index.php');
