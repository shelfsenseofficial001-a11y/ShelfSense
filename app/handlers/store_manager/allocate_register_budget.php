<?php
// app/handlers/store_manager/allocate_register_budget.php

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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    Response::error('Invalid request data. Please try again.', 400);
}

$registerId = isset($input['register_id']) ? intval($input['register_id']) : 0;
$initialBudget = isset($input['initial_budget']) ? floatval($input['initial_budget']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if ($registerId <= 0) {
    Response::error('Invalid register.', 400);
}

if ($initialBudget <= 0) {
    Response::error('Initial budget must be greater than 0', 400);
}

if ($initialBudget > 1000000) {
    Response::error('Initial budget is too large', 400);
}

try {
    $registerModel = new Register();
    $allocation = $registerModel->allocateBudget($registerId, Auth::userId(), $initialBudget, $notes);

    Response::success(['allocation' => $allocation], 'Budget allocated to register');

} catch (Exception $e) {
    error_log('allocate_register_budget.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
