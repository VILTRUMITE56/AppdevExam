<?php
require_once __DIR__ . '/db.php';
$error = '';
$values = ['first_name' => '', 'last_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'address' => '', 'age' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? null);
        foreach ($values as $field => $_) {
            $raw = $_POST[$field] ?? '';
            $values[$field] = is_string($raw) ? trim($raw) : '';
        }
        $limits = ['first_name' => 100, 'last_name' => 100, 'username' => 50, 'email' => 254, 'phone' => 32, 'address' => 255];
        foreach ($limits as $field => $limit) {
            if ($values[$field] === '') {
                throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
            $length = function_exists('mb_strlen') ? mb_strlen($values[$field], 'UTF-8') : strlen($values[$field]);
            if ($length > $limit) {
                throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $field)) . ' must be no more than ' . $limit . ' characters.');
            }
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (preg_match('/^[A-Za-z0-9._-]{3,50}$/', $values['username']) !== 1) {
            throw new InvalidArgumentException('Username must be 3–50 characters and use letters, numbers, dots, underscores, or hyphens.');
        }
        $age = null;
        if ($values['age'] !== '') {
            $age = filter_var($values['age'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);
            if ($age === false) {
                throw new InvalidArgumentException('Age must be a whole number between 1 and 120.');
            }
        }
        $password = $_POST['password'] ?? null;
        $confirm = $_POST['confirm_password'] ?? null;
        if (!is_string($password) || $password === '') {
            throw new InvalidArgumentException('Password is required.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long.');
        }
        if (strlen($password) > 72) {
            throw new InvalidArgumentException('Password must be no more than 72 bytes.');
        }
        if (!is_string($confirm) || !hash_equals($password, $confirm)) {
            throw new InvalidArgumentException('Passwords do not match.');
        }
        $check = $pdo->prepare('SELECT username, email FROM users WHERE username = :username OR email = :email');
        $check->execute(['username' => $values['username'], 'email' => $values['email']]);
        foreach ($check->fetchAll() as $existing) {
            if (strcasecmp($existing['username'], $values['username']) === 0) {
                throw new InvalidArgumentException('That username is already registered. Choose another username.');
            }
            if (strcasecmp($existing['email'], $values['email']) === 0) {
                throw new InvalidArgumentException('That email address is already registered. Use another email address.');
            }
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, username, email, phone, address, age, password_hash) '
            . 'VALUES (:first_name, :last_name, :username, :email, :phone, :address, :age, :password_hash)'
        );
        $stmt->execute([
            'first_name' => $values['first_name'], 'last_name' => $values['last_name'],
            'username' => $values['username'], 'email' => $values['email'], 'phone' => $values['phone'],
            'address' => $values['address'], 'age' => $age, 'password_hash' => $passwordHash,
        ]);
        header('Location: login.php?registered=1');
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log('Pet Grooming Salon registration failed: ' . $e->getMessage());
        $error = $e->getCode() === '23000'
            ? 'That username or email address is already registered.'
            : 'Registration could not be completed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Pet Grooming Salon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="auth-card auth-card-wide">
        <p class="eyebrow">Pet Grooming Salon</p><h1>Create an account</h1>
        <p class="muted">Register a user to manage salon records.</p>
        <?php if ($error !== ''): ?><p class="message error" role="alert"><?php echo escapeHtml($error); ?></p><?php endif; ?>
        <form method="POST" action="register.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>">
            <div class="form-grid">
                <label>First name<input type="text" name="first_name" maxlength="100" autocomplete="given-name" required value="<?php echo escapeHtml($values['first_name']); ?>"></label>
                <label>Last name<input type="text" name="last_name" maxlength="100" autocomplete="family-name" required value="<?php echo escapeHtml($values['last_name']); ?>"></label>
                <label>Username<input type="text" name="username" maxlength="50" autocomplete="username" required value="<?php echo escapeHtml($values['username']); ?>"></label>
                <label>Email<input type="email" name="email" maxlength="254" autocomplete="email" required value="<?php echo escapeHtml($values['email']); ?>"></label>
                <label>Phone<input type="tel" name="phone" maxlength="32" autocomplete="tel" required value="<?php echo escapeHtml($values['phone']); ?>"></label>
                <label>Age <span class="muted">(optional)</span><input type="number" name="age" min="1" max="120" value="<?php echo escapeHtml($values['age']); ?>"></label>
                <label class="full-width">Address<input type="text" name="address" maxlength="255" autocomplete="street-address" required value="<?php echo escapeHtml($values['address']); ?>"></label>
                <label>Password<input type="password" name="password" autocomplete="new-password" required></label>
                <label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" required></label>
            </div>
            <button type="submit">Register</button>
        </form>
        <p class="auth-switch">Already registered? <a href="login.php">Log in</a></p>
    </main>
</body>
</html>
