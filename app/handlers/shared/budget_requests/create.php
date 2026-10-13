<?php
// app/handlers/shared/budget_requests/create.php
// A department head requests Finance set their budget allocation to a
// given amount for a cutoff period, with a reason. Store Manager can only
// request for 'store', HR Head only for 'hr' -- each role requests for
// its own department, not anyone else's.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/BudgetRequest.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;
use App\Models\BudgetRequest;

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
if (!$roleDepartment && !Auth::isSuperAdmin()) {
    Response::forbidden('Only a Store Manager or HR Head can request a budget allocation.');
}

$input = json_decode(file_get_contents('php://input'), true);
$periodKey = isset($input['period_key']) ? trim($input['period_key']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : -1;
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$departmentName = $roleDepartment ?: (isset($input['department_name']) ? trim($input['department_name']) : '');

if (!preg_match('/^\d{4}-\d{2}-H[12]$/', $periodKey)) {
    Response::error('Invalid period key', 400);
}
if ($amount < 0) {
    Response::error('Amount must be non-negative', 400);
}
if ($reason === '' || strlen($reason) > 500) {
    Response::error('A reason (up to 500 characters) is required', 400);
}
if ($departmentName === '') {
    Response::error('Department is required', 400);
}

try {
    $budgetModel = new Budget();
    $department = $budgetModel->getDepartmentByName($departmentName);
    if (!$department) {
        Response::notFound('Department not found');
    }

    $requestModel = new BudgetRequest();
    $requestId = $requestModel->create($department['id'], $periodKey, $amount, $reason, Auth::userId());

    foreach (getUsersByRole('finance_head') as $u) {
        createNotification(
            $u['user_id'],
            'budget_request',
            "{$department['name']} requested a budget of ₱" . number_format($amount, 2) . " for " . (CutoffPeriod::describeKey($periodKey)['label'] ?? $periodKey) . ".",
            "?page=finance_head_budget"
        );
    }

    Response::success(['request_id' => $requestId], 'Budget request submitted.');
} catch (Exception $e) {
    error_log('budget_requests/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
