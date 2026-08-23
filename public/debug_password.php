<?php
// public/debug_password.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// Get user 1
$stmt = $db->prepare("SELECT user_id, email, password FROM users WHERE user_id = 1");
$stmt->execute();
$user = $stmt->fetch();

echo "<h2>🔐 Debug: User Password</h2>";
echo "<p><strong>User ID:</strong> " . $user['user_id'] . "</p>";
echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
echo "<p><strong>Password Hash:</strong> " . substr($user['password'], 0, 50) . "...</p>";

// Test with the password you set
echo "<h3>Enter your new password to test:</h3>";
echo "<form method='POST'>";
echo "<input type='password' name='test_password' placeholder='Enter your new password' style='padding:8px;width:300px;'>";
echo "<button type='submit' style='padding:8px 16px;'>Test</button>";
echo "</form>";

if (isset($_POST['test_password'])) {
    $testPassword = $_POST['test_password'];
    if (password_verify($testPassword, $user['password'])) {
        echo "<p style='color:green;font-weight:bold;'>✅ Password is correct! Login should work.</p>";
    } else {
        echo "<p style='color:red;font-weight:bold;'>❌ Password does NOT match.</p>";
        echo "<p>Try resetting your password again.</p>";
    }
}

echo "<h3>Manually Reset Password</h3>";
echo "<p>If you're stuck, run this SQL in phpMyAdmin:</p>";
echo "<pre style='background:#f4f4f4;padding:12px;border-radius:8px;'>";
echo "UPDATE users SET password = '" . password_hash('Password123!', PASSWORD_DEFAULT) . "' WHERE user_id = 1;";
echo "</pre>";
echo "<p><strong>Then login with:</strong> Password123!</p>";

// Show all users
echo "<h3>All Active Users</h3>";
$stmt = $db->query("SELECT user_id, email, role FROM users WHERE is_active = 1");
$users = $stmt->fetchAll();
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Email</th><th>Role</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['user_id']}</td><td>{$u['email']}</td><td>{$u['role']}</td></tr>";
}
echo "</table>";