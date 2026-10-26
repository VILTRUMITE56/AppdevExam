<?php
require_once __DIR__ . '/db.php';
$error = '';
$identifier = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? null);
        $identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
        $password = $_POST['password'] ?? null;
        if ($identifier === '' || !is_string($password) || $password === '') {
            throw new InvalidArgumentException('Enter your username or email and password.');
        }
        $stmt = $pdo->prepare(
            'SELECT user_id, first_name, last_name, password_hash FROM users '
            . 'WHERE (username = :username OR email = :email) AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['username' => $identifier, 'email' => $identifier]);
        $user = $stmt->fetch();
        if ($user === false || !password_verify($password, $user['password_hash'])) {
            throw new DomainException('Invalid credentials.');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
    } catch (InvalidArgumentException | DomainException $e) {
        $error = $e instanceof DomainException ? 'Invalid username/email or password.' : $e->getMessage();
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon login failed: ' . $e->getMessage());
        $error = 'Login could not be completed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <p class="eyebrow">Pet Grooming Salon</p><h1>Log in</h1>
        <?php if (isset($_GET['registered'])): ?><p class="message success">Account created. You can log in now.</p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>
        <form method="POST" action="login.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
            <label>Username or email<input type="text" name="identifier" autocomplete="username" required value="<?php echo escapeHtml($identifier); ?>"></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit">Log in</button>
        </form>
        <p class="auth-switch">Need an account? <a href="register.php">Register</a></p>
    </main>
</body>
</html>
