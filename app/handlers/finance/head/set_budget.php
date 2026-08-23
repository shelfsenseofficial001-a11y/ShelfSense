<?php
// app/handlers/finance/head/set_budget.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$department = isset($input['department']) ? trim($input['department']) : 'store';
$monthYear = isset($input['month_year']) ? trim($input['month_year']) : date('Y-m');
$amount = isset($input['allocated_budget']) ? floatval($input['allocated_budget']) : 0;

if (empty($department) || strlen($department) > 20) {
    Response::error('A valid department is required', 400);
}
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthYear)) {
    Response::error('Budget period must be a valid YYYY-MM month', 400);
}
if ($amount <= 0 || !is_finite($amount)) {
    Response::error('Budget amount must be a valid number greater than 0', 400);
}

try {
    $budgetModel = new Budget();
    $result = $budgetModel->setAllocatedBudget($department, $monthYear, $amount);

    if ($result) {
        $budget = $budgetModel->getOrCreate($department, $monthYear);
        Response::success([
            'budget' => $budget
        ], 'Budget set successfully');
    } else {
        Response::error('Failed to set budget', 500);
    }

} catch (Exception $e) {
    error_log('set_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}