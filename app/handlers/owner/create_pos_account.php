<?php
// app/handlers/owner/create_pos_account.php

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
$storeManagerId = isset($input['store_manager_id']) ? intval($input['store_manager_id']) : 0;
$pin = isset($input['pin']) ? trim($input['pin']) : '';

if ($storeManagerId <= 0) {
    Response::error('Please select a store manager.', 400);
}

if (!preg_match('/^\d{4}$/', $pin)) {
    Response::error('PIN must be exactly 4 digits.', 400);
}

try {
    $registerModel = new Register();
    $register = $registerModel->createPosAccount($storeManagerId, $pin, Auth::userId());
    Response::success(['register' => $register], 'POS account created');
} catch (Exception $e) {
    error_log('create_pos_account.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
