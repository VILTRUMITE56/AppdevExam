<?php
require_once __DIR__ . '/auth.php';
requireAuth($pdo);
require_once __DIR__ . '/functions/view.php';
require_once __DIR__ . '/functions/layout.php';
try {
    $owners = viewOwnersWithPets($pdo);
    $error = '';
} catch (PDOException $e) {
    error_log('Pet Grooming Salon owner and pet view failed: ' . $e->getMessage());
    $owners = [];
    $error = 'Unable to load owners and pets. Check the database setup and reload this page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Owners and Pets | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php renderNavigation('view'); ?>
    <main class="page-shell">
        <div class="page-title"><div><p class="eyebrow">Parent and child relationship</p><h1>Owners and Their Pets</h1></div><p class="muted">Owners without pets remain visible in this view.</p></div>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>
        <?php foreach ($owners as $o): ?>
            <section class="card relationship-card">
                <h2><?php echo escapeHtml($o['owner_name']); ?> <span class="record-id">Owner #<?php echo (int) $o['owner_id']; ?></span></h2>
                <p>Phone: <?php echo escapeHtml($o['phone']); ?> <span class="separator">|</span> Email: <?php echo escapeHtml($o['email'] ?? ''); ?></p>
                <p class="attribution">Added by <?php echo escapeHtml($o['added_by_name'] ?? 'Legacy / Unknown'); ?> at <?php echo escapeHtml($o['added_at'] ?? 'Unknown'); ?>
                    <span class="separator">|</span> Last updated by <?php echo escapeHtml($o['last_updated'] === null ? 'Not updated yet' : ($o['last_updated_by_name'] ?? 'Legacy / Unknown')); ?> at <?php echo escapeHtml($o['last_updated'] ?? 'Not updated yet'); ?></p>
                <?php if ($o['pets'] === []): ?>
                    <p class="empty-state">No pets registered for this owner.</p>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Pet ID</th><th>Pet name</th><th>Species</th><th>Breed</th><th>Grooming date</th><th>Status</th><th>Added by</th><th>Added at</th><th>Last updated by</th><th>Last updated</th></tr></thead>
                        <tbody><?php foreach ($o['pets'] as $p): ?><tr>
                            <td><?php echo (int) $p['pet_id']; ?></td><td><?php echo escapeHtml($p['pet_name']); ?></td><td><?php echo escapeHtml($p['species']); ?></td><td><?php echo escapeHtml($p['breed'] ?? ''); ?></td><td><?php echo escapeHtml($p['grooming_date'] ?? ''); ?></td><td><?php echo escapeHtml($p['status']); ?></td>
                            <td><?php echo escapeHtml($p['added_by_name'] ?? 'Legacy / Unknown'); ?></td><td><?php echo escapeHtml($p['added_at'] ?? 'Unknown'); ?></td>
                            <td><?php echo escapeHtml($p['last_updated'] === null ? 'Not updated yet' : ($p['last_updated_by_name'] ?? 'Legacy / Unknown')); ?></td><td><?php echo escapeHtml($p['last_updated'] ?? 'Not updated yet'); ?></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        <?php if ($owners === []): ?><p class="empty-state">No owners found.</p><?php endif; ?>
    </main>
</body>
</html>
