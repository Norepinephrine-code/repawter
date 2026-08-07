<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (is_logged_in()) {
    redirect('/foster/my_applications.php');
}

redirect('/foster/apply.php');
