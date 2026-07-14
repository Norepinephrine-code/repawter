<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

// The main navbar links to /profile/ — forward to the canonical profile page.
redirect('/auth/profile.php');
