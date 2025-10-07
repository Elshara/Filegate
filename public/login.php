<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/layout.php';

if (current_user()) {
    redirect('/');
}

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = authenticate($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        $_SESSION['flash'] = ['type' => 'alert-success', 'text' => $result['message']];
        redirect('/');
    } else {
        $message = ['type' => 'alert-error', 'text' => $result['message']];
    }
}

render_header('Log in to Filegate');
?>
<section class="card">
    <h2>Welcome back</h2>
    <?php if ($message): ?>
        <div class="alert <?= e($message['type']) ?>"><?= e($message['text']) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required value="<?= e($_POST['username'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Log in</button>
    </form>
    <p>Need an account? <a href="/register.php">Create one now</a>.</p>
</section>
<?php
render_footer();
