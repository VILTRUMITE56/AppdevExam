<?php
require_once __DIR__ . '/auth.php';
requireAuth($pdo);
require_once __DIR__ . '/functions/layout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php renderNavigation('home'); ?>
    <main class="home page-shell">
        <div class="home-container">
            <p class="eyebrow">Salon workspace</p>
            <h1>Pet Grooming Salon</h1>
            <p>Manage pet owners and their pets, and review the user activity recorded for each change.</p>
            <div class="home-nav">
                <a href="owners.php">Manage Pet Owners</a>
                <a href="pets.php">Manage Pets</a>
                <a href="view.php">View Owners &amp; Pets</a>
                <a href="audit_logs.php">Audit Logs</a>
            </div>
        </div>
    </main>
</body>
</html>
