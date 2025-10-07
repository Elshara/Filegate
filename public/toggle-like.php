<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

if ($postId > 0) {
    toggle_like((int) $_SESSION['user_id'], $postId);
}

redirect($_SERVER['HTTP_REFERER'] ?? '/');
