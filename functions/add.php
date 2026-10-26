<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/audit.php';

// Validate a required or optional text field and enforce its schema length.
function salonTextField($value, string $label, int $maxLength, bool $required = true): ?string
{
    $text = is_string($value) ? trim($value) : '';
    if ($required && $text === '') {
        throw new InvalidArgumentException($label . ' is required.');
    }
    if ($text === '') {
        return null;
    }
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : preg_match_all('/./us', $text);
    if ($length === false || $length > $maxLength) {
        throw new InvalidArgumentException($label . ' must be no more than ' . $maxLength . ' characters.');
    }
    return $text;
}

function salonOwnerInput($ownerName, $phone, $email): array
{
    $name = salonTextField($ownerName, 'Owner name', 100);
    $phoneValue = salonTextField($phone, 'Phone', 20);
    $emailValue = salonTextField($email, 'Email', 100, false);
    if ($emailValue !== null && filter_var($emailValue, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    return [$name, $phoneValue, $emailValue];
}

function salonPetInput(PDO $pdo, $petName, $species, $breed, $groomingDate, $status, $ownerId): array
{
    $name = salonTextField($petName, 'Pet name', 100);
    $speciesValue = salonTextField($species, 'Species', 50);
    $breedValue = salonTextField($breed, 'Breed', 50, false);
    $dateValue = salonTextField($groomingDate, 'Grooming date', 10, false);
    $statusValue = salonTextField($status, 'Status', 50);
    if (!in_array($statusValue, ['Pending', 'In Progress', 'Completed', 'Cancelled'], true)) {
        throw new InvalidArgumentException('Select a valid grooming status.');
    }
    if ($dateValue !== null) {
        $date = DateTime::createFromFormat('!Y-m-d', $dateValue);
        if ($date === false || $date->format('Y-m-d') !== $dateValue) {
            throw new InvalidArgumentException('Enter a valid grooming date.');
        }
    }
    $ownerIdValue = validateRecordId($ownerId, 'owner');
    $ownerCheck = $pdo->prepare('SELECT 1 FROM pet_owners WHERE owner_id = :owner_id');
    $ownerCheck->execute(['owner_id' => $ownerIdValue]);
    if ($ownerCheck->fetchColumn() === false) {
        throw new InvalidArgumentException('Select an existing owner for this pet.');
    }
    return [$name, $speciesValue, $breedValue, $dateValue, $statusValue, $ownerIdValue];
}

function addOwner(PDO $pdo, $ownerName, $phone, $email, int $userId): void
{
    [$name, $phoneValue, $emailValue] = salonOwnerInput($ownerName, $phone, $email);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO pet_owners (owner_name, phone, email, added_by, added_at) '
            . 'VALUES (:owner_name, :phone, :email, :added_by, CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['owner_name' => $name, 'phone' => $phoneValue, 'email' => $emailValue, 'added_by' => $userId]);
        $id = (int) $pdo->lastInsertId();
        logAuditEvent($pdo, $userId, 'INSERT', 'Pet Owner', $id, null, loadAuditSnapshot($pdo, 'Pet Owner', $id));
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function addPet(PDO $pdo, $petName, $species, $breed, $groomingDate, $status, $ownerId, int $userId): void
{
    [$name, $speciesValue, $breedValue, $dateValue, $statusValue, $ownerIdValue] =
        salonPetInput($pdo, $petName, $species, $breed, $groomingDate, $status, $ownerId);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO pets (pet_name, species, breed, grooming_date, status, owner_id, added_by, added_at) '
            . 'VALUES (:pet_name, :species, :breed, :grooming_date, :status, :owner_id, :added_by, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'pet_name' => $name,
            'species' => $speciesValue,
            'breed' => $breedValue,
            'grooming_date' => $dateValue,
            'status' => $statusValue,
            'owner_id' => $ownerIdValue,
            'added_by' => $userId,
        ]);
        $id = (int) $pdo->lastInsertId();
        logAuditEvent($pdo, $userId, 'INSERT', 'Pet', $id, null, loadAuditSnapshot($pdo, 'Pet', $id));
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
