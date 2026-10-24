<?php
session_start();

if (isset($_SESSION['user'])) {
    $redirectPage = ($_SESSION['role'] ?? 'resident') === 'admin' ? 'admin_dashboard.php' : 'resident_dashboard.php';
    header('Location: ' . $redirectPage);
    exit;
}

require 'db.php';

$error = '';

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} else {
    $success = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $role = strtolower($user['role'] ?? 'resident');
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = in_array($role, ['admin', 'resident'], true) ? $role : 'resident';

                $redirectPage = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'resident_dashboard.php';
                header('Location: ' . $redirectPage);
                exit;
            }

            $error = 'Invalid username or password.';
        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-box">
        <div class="brand"><span class="brand-dot"></span> Barangay Digital Access</div>
        <h2>Welcome Back</h2>
        <div class="subtitle">Citizen and resident login portal</div>

        <?php if ($success): ?>
            <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Access Portal</button>
        </form>

        <a class="link" href="register.php">Create a new account</a>
    </div>
</body>
</html>
