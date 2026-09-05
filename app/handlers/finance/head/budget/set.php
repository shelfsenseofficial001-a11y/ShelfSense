<?php
// app/handlers/finance/head/budget/set.php

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$departmentId = isset($input['department_id']) ? intval($input['department_id']) : 0;
$periodKey = isset($input['period_key']) ? trim($input['period_key']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : -1;
$reason = isset($input['reason']) ? trim($input['reason']) : null;

if ($departmentId <= 0 || $periodKey === '' || $amount < 0 || !is_finite($amount)) {
    Response::error('Invalid request', 400);
}
if (!preg_match('/^\d{4}-\d{2}-H[12]$/', $periodKey)) {
    Response::error('Invalid period key format', 400);
}

try {
    $budgetModel = new Budget();
    $result = $budgetModel->adjustAllocation($departmentId, $periodKey, $amount, Auth::userId(), $reason);
    Response::success($result, 'Budget allocation updated' . ($result['below_committed'] ? ' (below current committed amount)' : ''));
} catch (Exception $e) {
    error_log('budget/set.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
