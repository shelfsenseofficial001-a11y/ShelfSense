<?php
// app/handlers/auth/request_password_reset.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../models/User.php';

use App\Core\Response;
use App\Core\Mailer;
use App\Models\User;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';

if (empty($email)) {
    Response::error('Email is required', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email address', 400);
}

try {
    $userModel = new User();
    $user = $userModel->getByEmail($email);

    if (!$user) {
        // Security: don't reveal if email exists
        Response::success([], 'If an account exists with this email, an OTP has been sent.');
    }

    // Generate OTP
    $otp = $userModel->generateOTP();

    // Clear old OTPs for this user
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    // Save new OTP with MySQL expiration
    $stmt = $db->prepare("
        INSERT INTO password_resets (user_id, otp, expires_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
    ");
    $stmt->execute([$user['user_id'], $otp]);
    $resetId = $db->lastInsertId();

    // Send email
    $mailer = new Mailer();
    $result = $mailer->sendPasswordResetOTP($user, $otp);

    if (!$result['success']) {
        error_log('Failed to send OTP email: ' . $result['message']);
        Response::error('Failed to send OTP. Please try again.', 500);
    }

    Response::success([
        'user_id' => $user['user_id'],
        'reset_id' => $resetId
    ], 'OTP sent to your email address.');

} catch (Exception $e) {
    error_log('request_password_reset.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), 500);
}