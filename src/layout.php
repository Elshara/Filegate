<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function render_header(string $title = 'Filegate'): void
{
    $user = current_user();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="/assets/style.css">
    </head>
    <body>
    <header class="site-header">
        <div class="container header-content">
            <h1 class="logo"><a href="/">Filegate</a></h1>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <?php if ($user): ?>
                        <li><a href="/profile.php">Profile</a></li>
                        <li><a href="/settings.php">Settings</a></li>
                        <li><form method="post" action="/logout.php"><button type="submit" class="link-button">Log out</button></form></li>
                    <?php else: ?>
                        <li><a href="/login.php">Log in</a></li>
                        <li><a href="/register.php" class="button">Sign up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Filegate. Built for rapid community building.</p>
        </div>
    </footer>
    </body>
    </html>
    <?php
}
