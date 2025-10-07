<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/layout.php';

$db = get_db();
$user = current_user();
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$posts = fetch_feed($db);

render_header('Filegate — Home');
?>
<div class="grid">
    <?php if ($message): ?>
        <div class="alert <?= e($message['type']) ?>">
            <?= e($message['text']) ?>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
        <section class="card">
            <h2>Share something</h2>
            <form action="/post.php" method="post">
                <label for="content">What's happening?</label>
                <textarea name="content" id="content" maxlength="500" required></textarea>
                <div style="display:flex; justify-content: flex-end;">
                    <button type="submit">Post</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <h2>Welcome to Filegate</h2>
            <p>Connect instantly with your community. <a href="/register.php">Create an account</a> or <a href="/login.php">log in</a> to start posting.</p>
        </section>
    <?php endif; ?>

    <section>
        <h2>Latest activity</h2>
        <div class="feed">
            <?php if (!$posts): ?>
                <p class="muted">No posts yet. Start the conversation!</p>
            <?php endif; ?>
            <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post-header">
                        <span class="name"><a href="/profile.php?id=<?= (int) $post['user_id'] ?>"><?= e($post['display_name']) ?></a></span>
                        <span class="username">@<?= e($post['username']) ?></span>
                        <span class="username">&middot; <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></span>
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
</div>
<?php
render_footer();
