<?php
require_once __DIR__ . '/auth.php';
requireAuth($pdo);
require_once __DIR__ . '/functions/audit.php';
require_once __DIR__ . '/functions/layout.php';
$error = '';
try {
    $events = viewAuditLogs($pdo);
} catch (PDOException $e) {
    error_log('Pet Grooming Salon audit list failed: ' . $e->getMessage());
    $events = [];
    $error = 'Unable to load audit history. Check the database setup and reload this page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Logs | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php renderNavigation('audit'); ?>
    <main class="page-shell">
        <div class="page-title"><div><p class="eyebrow">Accountability history</p><h1>Audit Logs</h1></div><p class="muted">Successful inserts, updates, and deletions appear newest first.</p></div>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>
        <section class="card">
            <div class="table-wrap"><table class="audit-table">
                <thead><tr><th>Timestamp</th><th>Performed by</th><th>Action</th><th>Entity</th><th>Record ID</th><th>Old values</th><th>New values</th></tr></thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <?php $actor = trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')); ?>
                    <?php $old = decodeAuditValues($event['old_values']); $new = decodeAuditValues($event['new_values']); ?>
                    <tr>
                        <td><?php echo escapeHtml($event['performed_at']); ?></td>
                        <td><?php echo escapeHtml($actor !== '' ? $actor : 'Former / Unknown'); ?></td>
                        <td><span class="action-badge action-<?php echo strtolower(escapeHtml($event['action'])); ?>"><?php echo escapeHtml($event['action']); ?></span></td>
                        <td><?php echo escapeHtml($event['entity_type']); ?></td>
                        <td><?php echo (int) $event['record_id']; ?></td>
                        <td><?php if ($old !== null): ?><details><summary>View details</summary><pre><?php echo escapeHtml(json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre></details><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                        <td><?php if ($new !== null): ?><details><summary>View details</summary><pre><?php echo escapeHtml(json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre></details><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($events === []): ?><tr><td colspan="7" class="empty-state">No audit events have been recorded yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </main>
</body>
</html>
