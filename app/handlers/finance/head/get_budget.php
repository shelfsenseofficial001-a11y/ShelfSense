<?php
// app/handlers/finance/head/get_budget.php

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

$department = isset($_GET['department']) ? trim($_GET['department']) : 'store';
$monthYear = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');

try {
    $budgetModel = new Budget();

    // ✅ Get summary without auto-creating
    $summary = $budgetModel->getSummary($department, $monthYear);

    // Get all departments budgets for this month for chart
    $allBudgets = $budgetModel->getAllForMonth($monthYear);

    // If no budgets exist for this month, return empty array
    if (!$allBudgets) {
        $allBudgets = [];
    }

    Response::success([
        'budget' => [
            'allocated_budget' => $summary['allocated'],
            'used_budget' => $summary['used'],
            'department' => $summary['department'],
            'month_year' => $summary['month_year']
        ],
        'remaining' => $summary['remaining'],
        'department' => $department,
        'month_year' => $monthYear,
        'all_budgets' => $allBudgets,
        'exists' => $summary['exists']
    ], 'Budget fetched');

} catch (Exception $e) {
    error_log('get_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}