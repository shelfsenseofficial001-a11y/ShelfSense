<?php
// app/handlers/finance/head/set_budget.php
// Creates or adjusts a department/period's allocated budget, keeping a truthful
// adjustment history (see Budget::adjustAllocation()). Never overwrites used_budget.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
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
$department = isset($input['department']) ? trim($input['department']) : '';
$monthYear = isset($input['month_year']) ? trim($input['month_year']) : '';
$amount = isset($input['allocated_budget']) ? $input['allocated_budget'] : null;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($department === '' || strlen($department) > 20 || !preg_match('/^[a-z_]+$/', $department)) {
    Response::error('A valid department is required', 400);
}
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthYear)) {
    Response::error('Budget period must be a valid YYYY-MM month', 400);
}
if (!is_numeric($amount) || !is_finite((float)$amount) || (float)$amount < 0) {
    Response::error('Budget amount must be a valid number that is 0 or greater', 400);
}
if (strlen($reason) > 500) {
    Response::error('Reason cannot exceed 500 characters', 400);
}
$amount = round((float)$amount, 2);

try {
    $budgetModel = new Budget();
    $result = $budgetModel->adjustAllocation($department, $monthYear, $amount, Auth::userId(), $reason !== '' ? $reason : null);

    $status = $budgetModel->getBudgetStatus($department, $monthYear);

    Response::success([
        'adjustment' => $result,
        'budget_status' => $status
    ], $result['adjustment_amount'] == 0
        ? 'Budget allocation saved (no change from previous amount).'
        : ($result['adjustment_amount'] > 0 ? 'Budget allocation increased.' : 'Budget allocation decreased.')
        . ($result['below_committed'] ? ' Warning: new allocation is below already used/reserved budget.' : '')
    );

} catch (Exception $e) {
    error_log('finance/head/set_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
