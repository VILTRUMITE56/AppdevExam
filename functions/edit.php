<?php
require_once __DIR__ . '/add.php';

function editOwner(PDO $pdo, $ownerId, $ownerName, $phone, $email, int $userId): void
{
    $id = validateRecordId($ownerId, 'owner');
    [$name, $phoneValue, $emailValue] = salonOwnerInput($ownerName, $phone, $email);
    $pdo->beginTransaction();
    try {
        $before = loadAuditSnapshot($pdo, 'Pet Owner', $id);
        $stmt = $pdo->prepare(
            'UPDATE pet_owners SET owner_name = :owner_name, phone = :phone, email = :email, '
            . 'last_updated_by = :last_updated_by, last_updated = CURRENT_TIMESTAMP WHERE owner_id = :owner_id'
        );
        $stmt->execute([
            'owner_name' => $name,
            'phone' => $phoneValue,
            'email' => $emailValue,
            'last_updated_by' => $userId,
            'owner_id' => $id,
        ]);
        $after = loadAuditSnapshot($pdo, 'Pet Owner', $id);
        logAuditEvent($pdo, $userId, 'UPDATE', 'Pet Owner', $id, $before, $after);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function editPet(PDO $pdo, $petId, $petName, $species, $breed, $groomingDate, $status, $ownerId, int $userId): void
{
    $id = validateRecordId($petId, 'pet');
    [$name, $speciesValue, $breedValue, $dateValue, $statusValue, $ownerIdValue] =
        salonPetInput($pdo, $petName, $species, $breed, $groomingDate, $status, $ownerId);
    $pdo->beginTransaction();
    try {
        $before = loadAuditSnapshot($pdo, 'Pet', $id);
        $stmt = $pdo->prepare(
            'UPDATE pets SET pet_name = :pet_name, species = :species, breed = :breed, '
            . 'grooming_date = :grooming_date, status = :status, owner_id = :owner_id, '
            . 'last_updated_by = :last_updated_by, last_updated = CURRENT_TIMESTAMP WHERE pet_id = :pet_id'
        );
        $stmt->execute([
            'pet_name' => $name,
            'species' => $speciesValue,
            'breed' => $breedValue,
            'grooming_date' => $dateValue,
            'status' => $statusValue,
            'owner_id' => $ownerIdValue,
            'last_updated_by' => $userId,
            'pet_id' => $id,
        ]);
        $after = loadAuditSnapshot($pdo, 'Pet', $id);
        logAuditEvent($pdo, $userId, 'UPDATE', 'Pet', $id, $before, $after);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
