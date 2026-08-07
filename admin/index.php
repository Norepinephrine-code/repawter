<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

redirect('/admin/dashboard.php');
