<?php
declare(strict_types=1);

session_start();

define('DB_PATH', __DIR__ . '/../data/social.db');

function get_db(): PDO
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $needInit = !file_exists(DB_PATH);

    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($needInit) {
        initialize_database($db);
    }

    return $db;
}

function initialize_database(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        display_name TEXT NOT NULL,
        bio TEXT DEFAULT "",
        location TEXT DEFAULT "",
        website TEXT DEFAULT "",
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS likes (
        user_id INTEGER NOT NULL,
        post_id INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, post_id),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
    )');
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function register_user(string $username, string $displayName, string $password): array
{
    $username = strtolower(trim($username));
    $displayName = trim($displayName);

    if ($username === '' || $displayName === '' || $password === '') {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        return ['success' => false, 'message' => 'Username must be 3-30 characters using lowercase letters, numbers, or underscores.'];
    }

    if (mb_strlen($displayName) > 60) {
        return ['success' => false, 'message' => 'Display name must be 60 characters or fewer.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $db = get_db();

    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);

    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'That username is already taken.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (username, display_name, password_hash) VALUES (:username, :display_name, :password_hash)');
    $stmt->execute([
        ':username' => $username,
        ':display_name' => $displayName,
        ':password_hash' => $hash,
    ]);

    $_SESSION['user_id'] = (int) $db->lastInsertId();

    return ['success' => true, 'message' => 'Account created! Welcome to Filegate.'];
}

function authenticate(string $username, string $password): array
{
    $db = get_db();

    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute([':username' => strtolower(trim($username))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    $_SESSION['user_id'] = (int) $user['id'];

    return ['success' => true, 'message' => 'Welcome back, ' . htmlspecialchars($user['display_name']) . '!'];
}

function create_post(int $userId, string $content): array
{
    $content = trim($content);
    if ($content === '') {
        return ['success' => false, 'message' => 'Post content cannot be empty.'];
    }

    if (mb_strlen($content) > 500) {
        return ['success' => false, 'message' => 'Posts are limited to 500 characters.'];
    }

    $db = get_db();
    $stmt = $db->prepare('INSERT INTO posts (user_id, content) VALUES (:user_id, :content)');
    $stmt->execute([
        ':user_id' => $userId,
        ':content' => $content,
    ]);

    return ['success' => true, 'message' => 'Post published successfully.'];
}

function fetch_feed(PDO $db): array
{
    $stmt = $db->query('SELECT posts.*, users.display_name, users.username,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count
        FROM posts
        JOIN users ON users.id = posts.user_id
        ORDER BY posts.created_at DESC
        LIMIT 50');

    $posts = $stmt->fetchAll();

    $currentUser = current_user();

    if ($currentUser) {
        $stmtLiked = $db->prepare('SELECT post_id FROM likes WHERE user_id = :user_id');
        $stmtLiked->execute([':user_id' => $currentUser['id']]);
        $liked = $stmtLiked->fetchAll(PDO::FETCH_COLUMN);
        $liked = array_map('intval', $liked);
        $likedSet = array_fill_keys($liked, true);

        foreach ($posts as &$post) {
            $post['liked'] = isset($likedSet[(int) $post['id']]);
        }
    }

    return $posts;
}

function fetch_user_profile(int $userId): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT id, username, display_name, bio, location, website, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }

    $stmtPosts = $db->prepare('SELECT posts.*, (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count FROM posts WHERE user_id = :id ORDER BY created_at DESC');
    $stmtPosts->execute([':id' => $userId]);
    $user['posts'] = $stmtPosts->fetchAll();

    $current = current_user();
    if ($current) {
        $likedStmt = $db->prepare('SELECT post_id FROM likes WHERE user_id = :user_id AND post_id IN (SELECT id FROM posts WHERE user_id = :profile_id)');
        $likedStmt->execute([
            ':user_id' => $current['id'],
            ':profile_id' => $userId,
        ]);
        $liked = array_map('intval', $likedStmt->fetchAll(PDO::FETCH_COLUMN));
        $likedSet = array_fill_keys($liked, true);

        foreach ($user['posts'] as &$post) {
            $post['liked'] = isset($likedSet[(int) $post['id']]);
        }
    }

    return $user;
}

function update_profile(int $userId, array $data): array
{
    $db = get_db();
    $fields = [
        'display_name' => trim($data['display_name'] ?? ''),
        'bio' => trim($data['bio'] ?? ''),
        'location' => trim($data['location'] ?? ''),
        'website' => trim($data['website'] ?? ''),
    ];

    if ($fields['display_name'] === '') {
        return ['success' => false, 'message' => 'Display name is required.'];
    }

    if ($fields['bio'] !== '' && mb_strlen($fields['bio']) > 280) {
        return ['success' => false, 'message' => 'Bio is limited to 280 characters.'];
    }

    if ($fields['website'] !== '' && !filter_var($fields['website'], FILTER_VALIDATE_URL)) {
        return ['success' => false, 'message' => 'Please provide a valid website URL.'];
    }

    $stmt = $db->prepare('UPDATE users SET display_name = :display_name, bio = :bio, location = :location, website = :website, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        ':display_name' => $fields['display_name'],
        ':bio' => $fields['bio'],
        ':location' => $fields['location'],
        ':website' => $fields['website'],
        ':id' => $userId,
    ]);

    return ['success' => true, 'message' => 'Profile updated successfully.'];
}

function toggle_like(int $userId, int $postId): void
{
    $db = get_db();
    $stmt = $db->prepare('SELECT 1 FROM likes WHERE user_id = :user_id AND post_id = :post_id');
    $stmt->execute([
        ':user_id' => $userId,
        ':post_id' => $postId,
    ]);

    if ($stmt->fetch()) {
        $delete = $db->prepare('DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id');
        $delete->execute([
            ':user_id' => $userId,
            ':post_id' => $postId,
        ]);
    } else {
        $insert = $db->prepare('INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)');
        $insert->execute([
            ':user_id' => $userId,
            ':post_id' => $postId,
        ]);
    }
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
