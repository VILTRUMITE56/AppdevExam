<?php

function renderNavigation(string $activePage = ''): void
{
    $links = [
        'index.php' => ['Home', 'home'],
        'owners.php' => ['Manage Pet Owners', 'owners'],
        'pets.php' => ['Manage Pets', 'pets'],
        'view.php' => ['View Owners & Pets', 'view'],
        'audit_logs.php' => ['Audit Logs', 'audit'],
    ];
    ?>
    <header class="site-header">
        <a class="brand" href="index.php">Pet Grooming Salon</a>
        <nav class="nav" aria-label="Main navigation">
            <?php foreach ($links as $href => [$label, $key]): ?>
                <a href="<?php echo escapeHtml($href); ?>"<?php echo $activePage === $key ? ' aria-current="page"' : ''; ?>><?php echo escapeHtml($label); ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="account-bar">
            <span>Signed in as <strong><?php echo escapeHtml(currentUserName()); ?></strong></span>
            <form method="POST" action="logout.php" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
                <button type="submit">Log out</button>
            </form>
        </div>
    </header>
    <?php
}
