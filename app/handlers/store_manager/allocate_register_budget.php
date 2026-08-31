<?php
// app/handlers/store_manager/allocate_register_budget.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Database;
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

$cashierId = intval($input['cashier_id'] ?? 0);
$initialBudget = isset($input['initial_budget']) ? floatval($input['initial_budget']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if ($cashierId <= 0) {
    Response::error('Please select a cashier', 400);
}

if ($initialBudget <= 0) {
    Response::error('Initial budget must be greater than 0', 400);
}

if ($initialBudget > 1000000) {
    Response::error('Initial budget is too large', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'employee' AND is_active = 1");
    $stmt->execute([$cashierId]);
    if (!$stmt->fetch()) {
        Response::error('Selected cashier is not a valid active employee', 400);
    }

    $registerModel = new Register();
    $allocation = $registerModel->allocateBudget(Auth::userId(), $cashierId, $initialBudget, $notes);

    Response::success(['allocation' => $allocation], 'Budget allocated to register');

} catch (Exception $e) {
    error_log('allocate_register_budget.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
