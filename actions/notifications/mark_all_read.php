<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/notifications/');
}

csrf_verify();
require_login();

mark_all_read(user_id());

flash('success', 'All notifications marked as read.');
redirect('/notifications/');
