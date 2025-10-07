<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/layout.php';

require_login();

$user = current_user();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = update_profile((int) $user['id'], $_POST);
    $message = ['type' => $result['success'] ? 'alert-success' : 'alert-error', 'text' => $result['message']];
    if ($result['success']) {
        $user = current_user();
    }
}

render_header('Account settings — Filegate');
?>
<section class="card">
    <h2>Profile settings</h2>
    <p>Update how you appear to others across Filegate.</p>
    <?php if ($message): ?>
        <div class="alert <?= e($message['type']) ?>"><?= e($message['text']) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="display_name">Display name</label>
        <input type="text" name="display_name" id="display_name" required value="<?= e($user['display_name'] ?? '') ?>">

        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" maxlength="280" placeholder="Tell people a little about yourself."><?= e($user['bio'] ?? '') ?></textarea>

        <label for="location">Location</label>
        <input type="text" name="location" id="location" value="<?= e($user['location'] ?? '') ?>" placeholder="City, Country">

        <label for="website">Website</label>
        <input type="url" name="website" id="website" value="<?= e($user['website'] ?? '') ?>" placeholder="https://example.com">

        <div style="display:flex; gap: 0.75rem;">
            <button type="submit">Save changes</button>
            <a class="button secondary" href="/profile.php">View profile</a>
        </div>
    </form>
</section>
<?php
render_footer();
