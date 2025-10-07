<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/layout.php';

$user = current_user();
$requestedId = isset($_GET['id']) ? (int) $_GET['id'] : ($user['id'] ?? 0);

if ($requestedId <= 0) {
    redirect('/');
}

$profile = fetch_user_profile($requestedId);

if (!$profile) {
    render_header('Profile not found');
    ?>
    <section class="card">
        <h2>We couldn’t find that profile</h2>
        <p>The person you are looking for may have left Filegate. <a href="/">Return home</a>.</p>
    </section>
    <?php
    render_footer();
    exit;
}

$title = $profile['display_name'] . ' (@' . $profile['username'] . ') — Filegate';
render_header($title);
?>
<section class="card">
    <div class="profile-header">
        <div>
            <h2><?= e($profile['display_name']) ?></h2>
            <p class="muted">@<?= e($profile['username']) ?></p>
        </div>
        <div class="profile-meta">
            <span>Joined <?= date('F j, Y', strtotime($profile['created_at'])) ?></span>
            <?php if (!empty($profile['location'])): ?>
                <span>📍 <?= e($profile['location']) ?></span>
            <?php endif; ?>
            <?php if (!empty($profile['website'])): ?>
                <span>🔗 <a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($profile['website']) ?></a></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($profile['bio'])): ?>
            <p><?= nl2br(e($profile['bio'])) ?></p>
        <?php endif; ?>
        <?php if ($user && $user['id'] === $profile['id']): ?>
            <div>
                <a href="/settings.php" class="button secondary">Edit profile</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section>
    <h2><?= $user && $user['id'] === $profile['id'] ? 'Your posts' : 'Recent posts' ?></h2>
    <div class="feed">
        <?php if (!$profile['posts']): ?>
            <p class="muted">No posts yet.</p>
        <?php endif; ?>
        <?php foreach ($profile['posts'] as $post): ?>
            <article class="post">
                <div class="post-header">
                    <span class="username">Posted <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></span>
                </div>
                <div class="post-content"><?= nl2br(e($post['content'])) ?></div>
                <div class="post-actions">
                    <span class="like-count">❤️ <?= (int) $post['like_count'] ?></span>
                    <?php if ($user): ?>
                        <form action="/toggle-like.php" method="post">
                            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                            <button type="submit" class="secondary"><?= !empty($post['liked']) ? 'Unlike' : 'Like' ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
render_footer();
