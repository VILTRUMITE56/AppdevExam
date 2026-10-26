<?php

// User names are joined for display; nullable user IDs preserve unknown legacy history.
function viewOwners(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT o.owner_id, o.owner_name, o.phone, o.email, o.added_by, o.added_at, '
        . 'o.last_updated_by, o.last_updated, '
        . 'CONCAT(added.first_name, CHAR(32), added.last_name) AS added_by_name, '
        . 'CONCAT(updated.first_name, CHAR(32), updated.last_name) AS last_updated_by_name '
        . 'FROM pet_owners o '
        . 'LEFT JOIN users added ON added.user_id = o.added_by '
        . 'LEFT JOIN users updated ON updated.user_id = o.last_updated_by '
        . 'ORDER BY o.owner_id'
    );
    return $stmt->fetchAll();
}

function getOwner(PDO $pdo, $ownerId): ?array
{
    $id = validateRecordId($ownerId, 'owner');
    $stmt = $pdo->prepare(
        'SELECT owner_id, owner_name, phone, email, added_by, added_at, last_updated_by, last_updated '
        . 'FROM pet_owners WHERE owner_id = :owner_id'
    );
    $stmt->execute(['owner_id' => $id]);
    $owner = $stmt->fetch();
    return $owner === false ? null : $owner;
}

function viewPets(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT p.pet_id, p.pet_name, p.species, p.breed, p.grooming_date, p.status, p.owner_id, '
        . 'p.added_by, p.added_at, p.last_updated_by, p.last_updated, o.owner_name, '
        . 'CONCAT(added.first_name, CHAR(32), added.last_name) AS added_by_name, '
        . 'CONCAT(updated.first_name, CHAR(32), updated.last_name) AS last_updated_by_name '
        . 'FROM pets p INNER JOIN pet_owners o ON o.owner_id = p.owner_id '
        . 'LEFT JOIN users added ON added.user_id = p.added_by '
        . 'LEFT JOIN users updated ON updated.user_id = p.last_updated_by '
        . 'ORDER BY p.pet_id'
    );
    return $stmt->fetchAll();
}

function getPet(PDO $pdo, $petId): ?array
{
    $id = validateRecordId($petId, 'pet');
    $stmt = $pdo->prepare(
        'SELECT pet_id, pet_name, species, breed, grooming_date, status, owner_id, '
        . 'added_by, added_at, last_updated_by, last_updated FROM pets WHERE pet_id = :pet_id'
    );
    $stmt->execute(['pet_id' => $id]);
    $pet = $stmt->fetch();
    return $pet === false ? null : $pet;
}

function viewOwnersWithPets(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT o.owner_id, o.owner_name, o.phone, o.email, o.added_by, o.added_at, '
        . 'o.last_updated_by, o.last_updated, '
        . 'CONCAT(oa.first_name, CHAR(32), oa.last_name) AS owner_added_by_name, '
        . 'CONCAT(ou.first_name, CHAR(32), ou.last_name) AS owner_last_updated_by_name, '
        . 'p.pet_id, p.pet_name, p.species, p.breed, p.grooming_date, p.status, '
        . 'p.added_by AS pet_added_by, p.added_at AS pet_added_at, '
        . 'p.last_updated_by AS pet_last_updated_by, p.last_updated AS pet_last_updated, '
        . 'CONCAT(pa.first_name, CHAR(32), pa.last_name) AS pet_added_by_name, '
        . 'CONCAT(pu.first_name, CHAR(32), pu.last_name) AS pet_last_updated_by_name '
        . 'FROM pet_owners o '
        . 'LEFT JOIN users oa ON oa.user_id = o.added_by '
        . 'LEFT JOIN users ou ON ou.user_id = o.last_updated_by '
        . 'LEFT JOIN pets p ON p.owner_id = o.owner_id '
        . 'LEFT JOIN users pa ON pa.user_id = p.added_by '
        . 'LEFT JOIN users pu ON pu.user_id = p.last_updated_by '
        . 'ORDER BY o.owner_id, p.pet_id'
    );

    $owners = [];
    while ($row = $stmt->fetch()) {
        $ownerId = (int) $row['owner_id'];
        if (!isset($owners[$ownerId])) {
            $owners[$ownerId] = [
                'owner_id' => $ownerId,
                'owner_name' => $row['owner_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'added_by_name' => $row['owner_added_by_name'],
                'added_at' => $row['added_at'],
                'last_updated_by_name' => $row['owner_last_updated_by_name'],
                'last_updated' => $row['last_updated'],
                'pets' => [],
            ];
        }
        if ($row['pet_id'] !== null) {
            $owners[$ownerId]['pets'][] = [
                'pet_id' => $row['pet_id'],
                'pet_name' => $row['pet_name'],
                'species' => $row['species'],
                'breed' => $row['breed'],
                'grooming_date' => $row['grooming_date'],
                'status' => $row['status'],
                'added_by_name' => $row['pet_added_by_name'],
                'added_at' => $row['pet_added_at'],
                'last_updated_by_name' => $row['pet_last_updated_by_name'],
                'last_updated' => $row['pet_last_updated'],
            ];
        }
    }
    return array_values($owners);
}
