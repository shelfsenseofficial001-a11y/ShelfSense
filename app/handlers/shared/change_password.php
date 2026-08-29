<?php
// app/handlers/shared/change_password.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    Response::error('All fields are required', 400);
}

if ($newPassword !== $confirmPassword) {
    Response::error('New password and confirmation do not match', 400);
}

if (strlen($newPassword) < 8) {
    Response::error('New password must be at least 8 characters', 400);
}

$userId = Auth::userId();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    Response::error('Current password is incorrect', 400);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
if (!$stmt->execute([$hash, $userId])) {
    Response::error('Failed to update password', 500);
}

Response::success([], 'Password changed successfully');
