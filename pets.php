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
        if (isset($_POST['add_pet'])) {
            addPet($pdo, $_POST['pet_name'] ?? '', $_POST['species'] ?? '', $_POST['breed'] ?? '',
                $_POST['grooming_date'] ?? '', $_POST['status'] ?? '', $_POST['owner_id'] ?? null, $userId);
        } elseif (isset($_POST['update_pet'])) {
            editPet($pdo, $_POST['pet_id'] ?? null, $_POST['pet_name'] ?? '', $_POST['species'] ?? '',
                $_POST['breed'] ?? '', $_POST['grooming_date'] ?? '', $_POST['status'] ?? '',
                $_POST['owner_id'] ?? null, $userId);
        } elseif (isset($_POST['delete_pet'])) {
            deletePet($pdo, $_POST['delete_pet'], $userId);
        } else {
            throw new InvalidArgumentException('Invalid form submission.');
        }
        header('Location: pets.php');
        exit;
    } catch (InvalidArgumentException | OutOfBoundsException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon pet operation failed: ' . $e->getMessage());
        $error = 'The pet could not be saved or deleted because of a database error.';
    }
}

$editMode = isset($_GET['edit']) || isset($_POST['update_pet']);
$editPetId = null;
$editPet = null;
if ($editMode) {
    $editPetId = $_GET['edit'] ?? ($_POST['pet_id'] ?? null);
    try {
        $editPet = getPet($pdo, $editPetId);
        if ($editPet === null && $error === '') {
            $error = 'Pet not found.';
        }
    } catch (InvalidArgumentException $e) {
        if ($error === '') {
            $error = $e->getMessage();
        }
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon pet lookup failed: ' . $e->getMessage());
        if ($error === '') {
            $error = 'Unable to load the selected pet.';
        }
    }
}
$petForm = $editPet ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_pet']) || isset($_POST['update_pet']))) {
    $petForm = array_merge($petForm, $_POST);
}
$selectedOwnerId = is_scalar($petForm['owner_id'] ?? null) ? (string) $petForm['owner_id'] : '';

try {
    $pets = viewPets($pdo);
    $owners = viewOwners($pdo);
} catch (PDOException $e) {
    error_log('Pet Grooming Salon pet list failed: ' . $e->getMessage());
    $pets = [];
    $owners = [];
    if ($error === '') {
        $error = 'Unable to load pets and owners. Check the database setup and reload this page.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pets | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php renderNavigation('pets'); ?>
    <main class="page-shell">
        <div class="page-title"><div><p class="eyebrow">Child entity</p><h1>Pets</h1></div><p class="muted">Each pet is linked to one existing owner.</p></div>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>
        <form method="POST" action="pets.php" class="form-card">
            <h2><?php echo $editMode ? 'Edit Pet' : 'Add New Pet'; ?></h2>
            <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
            <?php if ($editMode): ?><input type="hidden" name="pet_id" value="<?php echo escapeHtml($petForm['pet_id'] ?? $editPetId ?? ''); ?>"><?php endif; ?>
            <div class="form-grid">
                <label>Pet name<input type="text" name="pet_name" maxlength="100" required value="<?php echo escapeHtml($petForm['pet_name'] ?? ''); ?>"></label>
                <label>Species<input type="text" name="species" maxlength="50" required value="<?php echo escapeHtml($petForm['species'] ?? ''); ?>"></label>
                <label>Breed <span class="muted">(optional)</span><input type="text" name="breed" maxlength="50" value="<?php echo escapeHtml($petForm['breed'] ?? ''); ?>"></label>
                <label>Grooming date<input type="date" name="grooming_date" value="<?php echo escapeHtml($petForm['grooming_date'] ?? ''); ?>"></label>
                <label>Status<select name="status"><?php foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status): ?><option value="<?php echo escapeHtml($status); ?>" <?php echo (($petForm['status'] ?? 'Pending') === $status) ? 'selected' : ''; ?>><?php echo escapeHtml($status); ?></option><?php endforeach; ?></select></label>
                <label>Owner<select name="owner_id" required><option value="">-- Select Owner --</option><?php foreach ($owners as $o): ?><option value="<?php echo (int) $o['owner_id']; ?>" <?php echo ($selectedOwnerId === (string) $o['owner_id']) ? 'selected' : ''; ?>><?php echo escapeHtml($o['owner_name']); ?></option><?php endforeach; ?></select></label>
            </div>
            <?php if ($owners === []): ?><p class="muted">Register an owner before adding a pet.</p><?php endif; ?>
            <?php if ($editMode): ?><button type="submit" name="update_pet">Update Pet</button> <a class="button secondary" href="pets.php">Cancel</a><?php else: ?><button type="submit" name="add_pet">Add Pet</button><?php endif; ?>
        </form>
        <section class="card">
            <h2>Pet records</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Pet ID</th><th>Pet name</th><th>Species</th><th>Breed</th><th>Grooming date</th><th>Status</th><th>Owner</th><th>Added by</th><th>Added at</th><th>Last updated by</th><th>Last updated</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($pets as $p): ?><tr>
                    <td><?php echo (int) $p['pet_id']; ?></td><td><?php echo escapeHtml($p['pet_name']); ?></td><td><?php echo escapeHtml($p['species']); ?></td><td><?php echo escapeHtml($p['breed'] ?? ''); ?></td><td><?php echo escapeHtml($p['grooming_date'] ?? ''); ?></td><td><?php echo escapeHtml($p['status']); ?></td><td><?php echo escapeHtml($p['owner_name']); ?></td>
                    <td><?php echo escapeHtml($p['added_by_name'] ?? 'Legacy / Unknown'); ?></td><td><?php echo escapeHtml($p['added_at'] ?? 'Unknown'); ?></td>
                    <td><?php echo escapeHtml($p['last_updated'] === null ? 'Not updated yet' : ($p['last_updated_by_name'] ?? 'Legacy / Unknown')); ?></td><td><?php echo escapeHtml($p['last_updated'] ?? 'Not updated yet'); ?></td>
                    <td class="actions"><a href="pets.php?edit=<?php echo (int) $p['pet_id']; ?>">Edit</a><form method="POST" action="pets.php" class="inline-form" onsubmit="return confirm('Delete this pet?');"><input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>"><button class="link-button danger-link" type="submit" name="delete_pet" value="<?php echo (int) $p['pet_id']; ?>">Delete</button></form></td>
                </tr><?php endforeach; ?>
                <?php if ($pets === []): ?><tr><td colspan="12" class="empty-state">No pets have been added yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </main>
</body>
</html>
