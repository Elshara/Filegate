<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/layout.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = register_user($_POST['username'] ?? '', $_POST['display_name'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        $_SESSION['flash'] = ['type' => 'alert-success', 'text' => $result['message']];
        redirect('/');
    } else {
        $message = ['type' => 'alert-error', 'text' => $result['message']];
    }
}

render_header('Create your Filegate account');
?>
<section class="card">
    <h2>Sign up</h2>
    <p>Create an account to start sharing updates with your community.</p>
    <?php if ($message): ?>
        <div class="alert <?= e($message['type']) ?>"><?= e($message['text']) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" placeholder="choose a username" required minlength="3" maxlength="30" pattern="[a-z0-9_]+" value="<?= e($_POST['username'] ?? '') ?>">

        <label for="display_name">Display name</label>
        <input type="text" name="display_name" id="display_name" placeholder="What should people call you?" required value="<?= e($_POST['display_name'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required minlength="6">

        <button type="submit">Create account</button>
    </form>
    <p>Already have an account? <a href="/login.php">Log in</a>.</p>
</section>
<?php
render_footer();
