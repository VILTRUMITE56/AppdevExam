<?php
require_once __DIR__ . '/db.php';

// Verify both the session identity and that the account still exists and is active.
function requireAuth(PDO $pdo): void
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId < 1) {
        header('Location: login.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT user_id, first_name, last_name FROM users WHERE user_id = :user_id AND is_active = 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon authentication check failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Unable to verify your account right now. Please try again later.');
    }

    if ($user === false) {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
        header('Location: login.php');
        exit;
    }

    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function currentUserId(): int
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId < 1) {
        throw new RuntimeException('Authentication is required for this action.');
    }

    return $userId;
}

function currentUserName(): string
{
    $name = $_SESSION['user_name'] ?? '';
    return is_string($name) && $name !== '' ? $name : 'Salon user';
}
