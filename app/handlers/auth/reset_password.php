<?php
// app/handlers/auth/reset_password.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/User.php';

use App\Core\Response;
use App\Models\User;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$resetId = isset($input['reset_id']) ? intval($input['reset_id']) : 0;
$password = isset($input['password']) ? trim($input['password']) : '';
$confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';

error_log("=== Password Reset Attempt ===");
error_log("Reset ID: $resetId");
error_log("Password length: " . strlen($password));

if ($resetId <= 0) {
    Response::error('Invalid reset ID', 400);
}

if (empty($password) || empty($confirmPassword)) {
    Response::error('Password is required', 400);
}

if ($password !== $confirmPassword) {
    Response::error('Passwords do not match', 400);
}

if (strlen($password) < 8) {
    Response::error('Password must be at least 8 characters', 400);
}

if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    Response::error('Password must contain at least one uppercase, one lowercase, and one number', 400);
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Get the reset request
    $stmt = $db->prepare("SELECT user_id FROM password_resets WHERE id = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$resetId]);
    $reset = $stmt->fetch();

    if (!$reset) {
        error_log("Reset failed: Invalid or expired reset ID: $resetId");
        Response::error('Invalid or expired reset request. Please request a new OTP.', 400);
    }

    error_log("Reset valid for user_id: " . $reset['user_id']);

    // Update password using the User model
    $userModel = new User();
    $result = $userModel->updatePassword($reset['user_id'], $password);

    if (!$result) {
        error_log("Failed to update password for user_id: " . $reset['user_id']);
        Response::error('Failed to update password. Please try again.', 500);
    }

    // Mark OTP as used
    $userModel->markOTPUsed($resetId);

    error_log("✅ Password updated successfully for user_id: " . $reset['user_id']);

    // Test the password immediately
    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$reset['user_id']]);
    $stored = $stmt->fetch();
    
    if (password_verify($password, $stored['password'])) {
        error_log("✅ Password verification passed immediately after reset");
    } else {
        error_log("❌ Password verification FAILED immediately after reset!");
        error_log("Stored hash: " . $stored['password']);
    }

    Response::success([], 'Password reset successfully. You can now login with your new password.');

} catch (Exception $e) {
    error_log('reset_password.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), 500);
}