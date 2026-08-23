<?php
// app/handlers/auth/verify_otp.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/User.php';

use App\Core\Response;
use App\Models\User;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$otp = isset($input['otp']) ? trim($input['otp']) : '';
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;

if (empty($otp)) {
    Response::error('OTP is required', 400);
}

if (strlen($otp) !== 6 || !ctype_digit($otp)) {
    Response::error('Invalid OTP format', 400);
}

if ($userId <= 0) {
    Response::error('User ID is required', 400);
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Verify OTP
    $stmt = $db->prepare("
        SELECT * FROM password_resets 
        WHERE user_id = ? AND otp = ? AND used = 0 AND expires_at > NOW()
    ");
    $stmt->execute([$userId, $otp]);
    $reset = $stmt->fetch();

    if (!$reset) {
        Response::error('Invalid or expired OTP. Please request a new one.', 400);
    }

    Response::success([
        'reset_id' => $reset['id'],
        'valid' => true
    ], 'OTP verified successfully. You can now reset your password.');

} catch (Exception $e) {
    error_log('verify_otp.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), 500);
}