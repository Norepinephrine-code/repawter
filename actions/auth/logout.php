<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

// Accept both GET (direct link) and POST (form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

logout();
redirect('/');
