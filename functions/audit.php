<?php

// Keep audit snapshots limited to non-secret business data.
function auditSafeValues(?array $values): ?string
{
    if ($values === null) {
        return null;
    }

    foreach (array_keys($values) as $key) {
        if (preg_match('/password|secret|token/i', (string) $key) === 1) {
            unset($values[$key]);
        }
    }

    return json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

// Insert one audit event within the caller's business transaction.
function logAuditEvent(
    PDO $pdo,
    int $performedBy,
    string $action,
    string $entityType,
    int $recordId,
    ?array $oldValues,
    ?array $newValues
): void {
    if (!in_array($action, ['INSERT', 'UPDATE', 'DELETE'], true)) {
        throw new InvalidArgumentException('Invalid audit action.');
    }
    if (!in_array($entityType, ['Pet Owner', 'Pet'], true)) {
        throw new InvalidArgumentException('Invalid audit entity type.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (performed_by, action, entity_type, record_id, old_values, new_values) '
        . 'VALUES (:performed_by, :action, :entity_type, :record_id, :old_values, :new_values)'
    );
    $stmt->execute([
        'performed_by' => $performedBy,
        'action' => $action,
        'entity_type' => $entityType,
        'record_id' => $recordId,
        'old_values' => auditSafeValues($oldValues),
        'new_values' => auditSafeValues($newValues),
    ]);
}

function loadAuditSnapshot(PDO $pdo, string $entityType, int $recordId): array
{
    if ($entityType === 'Pet Owner') {
        $stmt = $pdo->prepare(
            'SELECT owner_id, owner_name, phone, email, added_by, added_at, last_updated_by, last_updated '
            . 'FROM pet_owners WHERE owner_id = :record_id'
        );
    } elseif ($entityType === 'Pet') {
        $stmt = $pdo->prepare(
            'SELECT pet_id, pet_name, species, breed, grooming_date, status, owner_id, '
            . 'added_by, added_at, last_updated_by, last_updated FROM pets WHERE pet_id = :record_id'
        );
    } else {
        throw new InvalidArgumentException('Invalid audit entity type.');
    }

    $stmt->execute(['record_id' => $recordId]);
    $row = $stmt->fetch();
    if ($row === false) {
        throw new OutOfBoundsException(($entityType === 'Pet Owner' ? 'Owner' : 'Pet') . ' not found.');
    }

    return $row;
}

function viewAuditLogs(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT a.audit_id, a.performed_by, a.action, a.entity_type, a.record_id, '
        . 'a.old_values, a.new_values, a.performed_at, '
        . 'u.first_name, u.last_name '
        . 'FROM audit_logs a LEFT JOIN users u ON u.user_id = a.performed_by '
        . 'ORDER BY a.performed_at DESC, a.audit_id DESC'
    );
    return $stmt->fetchAll();
}

function decodeAuditValues($json): ?array
{
    if (!is_string($json) || $json === '') {
        return null;
    }

    $values = json_decode($json, true);
    return is_array($values) ? $values : ['details' => $json];
}
