<?php
require_once __DIR__ . '/auth.php';
requireAuth($pdo);
require_once __DIR__ . '/functions/add.php';
require_once __DIR__ . '/functions/edit.php';
require_once __DIR__ . '/functions/delete.php';
require_once __DIR__ . '/functions/view.php';
require_once __DIR__ . '/functions/layout.php';

$userId = currentUserId();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? null);
        if (isset($_POST['add_owner'])) {
            addOwner($pdo, $_POST['owner_name'] ?? '', $_POST['phone'] ?? '', $_POST['email'] ?? '', $userId);
        } elseif (isset($_POST['update_owner'])) {
            editOwner(
                $pdo,
                $_POST['owner_id'] ?? null,
                $_POST['owner_name'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['email'] ?? '',
                $userId
            );
        } elseif (isset($_POST['delete_owner'])) {
            deleteOwner($pdo, $_POST['delete_owner'], $userId);
        } else {
            throw new InvalidArgumentException('Invalid form submission.');
        }
        header('Location: owners.php');
        exit;
    } catch (InvalidArgumentException | OutOfBoundsException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon owner operation failed: ' . $e->getMessage());
        $error = 'The owner could not be saved or deleted because of a database error.';
    }
}

$editMode = isset($_GET['edit']) || isset($_POST['update_owner']);
$editOwner = null;
$editOwnerId = null;
if ($editMode) {
    $editOwnerId = $_GET['edit'] ?? ($_POST['owner_id'] ?? null);
    try {
        $editOwner = getOwner($pdo, $editOwnerId);
        if ($editOwner === null && $error === '') {
            $error = 'Owner not found.';
        }
    } catch (InvalidArgumentException $e) {
        if ($error === '') {
            $error = $e->getMessage();
        }
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon owner lookup failed: ' . $e->getMessage());
        if ($error === '') {
            $error = 'Unable to load the selected owner.';
        }
    }
}
$ownerForm = $editOwner ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_owner']) || isset($_POST['update_owner']))) {
    $ownerForm = array_merge($ownerForm, $_POST);
}

try {
    $owners = viewOwners($pdo);
} catch (PDOException $e) {
    error_log('Pet Grooming Salon owner list failed: ' . $e->getMessage());
    $owners = [];
    if ($error === '') {
        $error = 'Unable to load owners. Check the database setup and reload this page.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pet Owners | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php renderNavigation('owners'); ?>
    <main class="page-shell">
        <div class="page-title">
            <div><p class="eyebrow">Parent entity</p><h1>Pet Owners</h1></div>
            <p class="muted">Track owner records and the users who created or last updated them.</p>
        </div>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>

        <form method="POST" action="owners.php" class="form-card">
            <h2><?php echo $editMode ? 'Edit Owner' : 'Add New Owner'; ?></h2>
            <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="owner_id" value="<?php echo escapeHtml($ownerForm['owner_id'] ?? $editOwnerId ?? ''); ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>Owner name<input type="text" name="owner_name" maxlength="100" required value="<?php echo escapeHtml($ownerForm['owner_name'] ?? ''); ?>"></label>
                <label>Phone<input type="text" name="phone" maxlength="20" required value="<?php echo escapeHtml($ownerForm['phone'] ?? ''); ?>"></label>
                <label>Email <span class="muted">(optional)</span><input type="email" name="email" maxlength="100" value="<?php echo escapeHtml($ownerForm['email'] ?? ''); ?>"></label>
            </div>
            <?php if ($editMode): ?>
                <button type="submit" name="update_owner">Update Owner</button>
                <a class="button secondary" href="owners.php">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_owner">Add Owner</button>
            <?php endif; ?>
        </form>

        <section class="card">
            <h2>Owner records</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>ID</th><th>Owner name</th><th>Phone</th><th>Email</th><th>Added by</th><th>Added at</th><th>Last updated by</th><th>Last updated</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($owners as $o): ?>
                    <tr>
                        <td><?php echo (int) $o['owner_id']; ?></td>
                        <td><?php echo escapeHtml($o['owner_name']); ?></td>
                        <td><?php echo escapeHtml($o['phone']); ?></td>
                        <td><?php echo escapeHtml($o['email'] ?? ''); ?></td>
                        <td><?php echo escapeHtml($o['added_by_name'] ?? 'Legacy / Unknown'); ?></td>
                        <td><?php echo escapeHtml($o['added_at'] ?? 'Unknown'); ?></td>
                        <td><?php echo escapeHtml($o['last_updated'] === null ? 'Not updated yet' : ($o['last_updated_by_name'] ?? 'Legacy / Unknown')); ?></td>
                        <td><?php echo escapeHtml($o['last_updated'] ?? 'Not updated yet'); ?></td>
                        <td class="actions">
                            <a href="owners.php?edit=<?php echo (int) $o['owner_id']; ?>">Edit</a>
                            <form method="POST" action="owners.php" class="inline-form" onsubmit="return confirm('Delete this owner and all pets linked to them?');">
                                <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
                                <button class="link-button danger-link" type="submit" name="delete_owner" value="<?php echo (int) $o['owner_id']; ?>">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($owners === []): ?><tr><td colspan="9" class="empty-state">No owners have been added yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </main>
</body>
</html>
