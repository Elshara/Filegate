<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'alert-success', 'text' => 'You have been logged out.'];
}

redirect('/');
