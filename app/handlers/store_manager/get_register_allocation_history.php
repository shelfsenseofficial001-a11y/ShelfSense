<?php
// app/handlers/store_manager/get_register_allocation_history.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Register;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$registerId = isset($_GET['register_id']) ? intval($_GET['register_id']) : 0;
if ($registerId <= 0) {
    Response::error('Invalid register.', 400);
}

try {
    $registerModel = new Register();
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;

    $register = $registerModel->getById($registerId);
    if (!$register || (int)$register['store_manager_id'] !== Auth::userId()) {
        Response::error('Register not found.', 404);
    }

    $history = $registerModel->getAllocationHistory($registerId, $limit);

    Response::success(['history' => $history], 'Allocation history fetched');

} catch (Exception $e) {
    error_log('get_register_allocation_history.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
