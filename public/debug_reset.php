<?php
// public/debug_reset.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/User.php';

use App\Models\User;

$userModel = new User();

// Test updating password for user 1
$userId = 1;
$newPassword = 'Password123!';

$result = $userModel->updatePassword($userId, $newPassword);

echo "Update result: " . ($result ? '✅ Success' : '❌ Failed') . "<br>";

// Verify the password
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (password_verify($newPassword, $user['password'])) {
    echo "✅ Password verification passed!";
} else {
    echo "❌ Password verification failed. Hash: " . $user['password'];
}