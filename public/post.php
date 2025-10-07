<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$result = create_post((int) $_SESSION['user_id'], $_POST['content'] ?? '');

$_SESSION['flash'] = [
    'type' => $result['success'] ? 'alert-success' : 'alert-error',
    'text' => $result['message'],
];

redirect('/');
