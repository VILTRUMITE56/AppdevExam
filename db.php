<?php
$host = 'localhost';
$dbname = 'pet_grooming_db';
$username = 'root';
$password = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Pet Grooming Salon database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. For a fresh install, import Database/schema.sql; for an existing install, run Database/migrations/001_user_auth_audit.sql.');
}

// Escape values before rendering them in HTML or HTML attributes.
function escapeHtml($value): string
{
    if (!is_scalar($value) && $value !== null) {
        $value = '';
    }

    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Reject malformed record IDs before they reach a database query.
function validateRecordId($value, string $recordName = 'record'): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        throw new InvalidArgumentException('Please provide a valid ' . $recordName . ' ID.');
    }

    return $id;
}

// Require a valid session token for every state-changing request.
function verifyCsrfToken($token): void
{
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new InvalidArgumentException('Your form session expired. Refresh the page and try again.');
    }
}
