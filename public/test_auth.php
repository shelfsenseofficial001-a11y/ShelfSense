<?php
// public/test_auth.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/helpers/functions.php';

use App\Core\Auth;

session_start();

$message = '';
$userData = null;

// Check login status
if (Auth::check()) {
    $user = Auth::user();
    $userData = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'permission_level' => Auth::permissionLevel(),
        'fullname' => $user['first_name'] . ' ' . $user['last_name']
    ];
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $result = Auth::login($_POST['email'], $_POST['password']);
    if ($result) {
        header('Location: test_auth.php');
        exit;
    } else {
        $message = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    Auth::logout();
    header('Location: test_auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: auto; }
        .card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px; border-bottom: 1px solid #eee; }
        input, button { padding: 8px; width: 100%; margin: 4px 0; }
    </style>
</head>
<body>
    <h1>Auth Test</h1>

    <?php if ($message): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($userData): ?>
        <div class="card">
            <h2>✅ Logged In</h2>
            <table>
                <tr><td><strong>User ID</strong></td><td><?= $userData['user_id'] ?></td></tr>
                <tr><td><strong>Email</strong></td><td><?= htmlspecialchars($userData['email']) ?></td></tr>
                <tr><td><strong>Role</strong></td><td><?= htmlspecialchars($userData['role']) ?></td></tr>
                <tr><td><strong>Permission Level</strong></td><td><strong><?= $userData['permission_level'] ?></strong></td></tr>
                <tr><td><strong>Full Name</strong></td><td><?= htmlspecialchars($userData['fullname']) ?></td></tr>
            </table>

            <h3>Permission Checks</h3>
            <ul>
                <li>isSuperAdmin(): <?= Auth::isSuperAdmin() ? '✅ True' : '❌ False' ?></li>
                <li>canApprove() (level 4): <?= Auth::canApprove() ? '✅ True' : '❌ False' ?></li>
                <li>canEdit() (level 1): <?= Auth::canEdit() ? '✅ True' : '❌ False' ?></li>
                <li>hasPermission(0) (view): <?= Auth::hasPermission(0) ? '✅ True' : '❌ False' ?></li>
            </ul>

            <a href="?logout=1">Logout</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Login</h2>
            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@example.com" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <p><small>Use any seeded account (e.g., admin@shelfsense.com / Password123!)</small></p>
        </div>
    <?php endif; ?>

    <h3>Session Dump</h3>
    <pre><?php print_r($_SESSION); ?></pre>
</body>
</html>