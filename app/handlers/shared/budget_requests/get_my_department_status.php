<?php
// app/handlers/shared/budget_requests/get_my_department_status.php
// A department head's own budget status (allocated/reserved/used/available)
// for a period -- Store Manager sees 'store', HR Head sees 'hr'. Resolved
// from role, not a client-supplied department, so nobody can peek at
// another department's numbers through this endpoint.

require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$roleDepartment = null;
if (Auth::isStoreManager()) {
    $roleDepartment = 'store';
} elseif (Auth::isHRHead()) {
    $roleDepartment = 'hr';
}
if (!$roleDepartment) {
    Response::forbidden('Access denied.');
}

$periodKey = isset($_GET['period_key']) ? trim($_GET['period_key']) : CutoffPeriod::getCurrentKey();
if (!preg_match('/^\d{4}-\d{2}-H[12]$/', $periodKey)) {
    Response::error('Invalid period key', 400);
}

try {
    $budgetModel = new Budget();
    $department = $budgetModel->getDepartmentByName($roleDepartment);
    if (!$department) {
        Response::success(['status' => null, 'period_key' => $periodKey], 'No department budget set up yet.');
    }

    $status = $budgetModel->getBudgetStatus($department['id'], $periodKey);
    Response::success([
        'status' => $status,
        'period_key' => $periodKey,
        'recent_periods' => CutoffPeriod::getRecentHalves(),
    ], 'Budget status fetched successfully');
} catch (Exception $e) {
    error_log('budget_requests/get_my_department_status.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
