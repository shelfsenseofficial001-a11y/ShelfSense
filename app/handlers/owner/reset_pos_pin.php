<?php
// app/handlers/owner/reset_pos_pin.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Register;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$registerId = isset($input['register_id']) ? intval($input['register_id']) : 0;
$pin = isset($input['pin']) ? trim($input['pin']) : '';

if ($registerId <= 0) {
    Response::error('Invalid register.', 400);
}

if (!preg_match('/^\d{4}$/', $pin)) {
    Response::error('PIN must be exactly 4 digits.', 400);
}

try {
    $registerModel = new Register();
    $register = $registerModel->getById($registerId);
    if (!$register || empty($register['pos_id'])) {
        Response::error('This register has no POS account to reset.', 404);
    }

    $register = $registerModel->resetPosPin($registerId, $pin);
    Response::success(['register' => $register], 'PIN reset');
} catch (Exception $e) {
    error_log('reset_pos_pin.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
