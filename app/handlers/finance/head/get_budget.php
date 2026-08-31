<?php
// app/handlers/finance/head/get_budget.php
// Finance Head Budget Management data: real per-department status for the
// selected period, near-limit warnings, and real usage-by-requisition for the
// currently selected department. Same Budget::getBudgetStatus() definitions
// used by Finance Staff — allocated / used / reserved / available / shortfall.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;
use App\Models\StoreRequisition;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$monthYear = isset($_GET['month']) ? trim($_GET['month']) : CutoffPeriod::getCurrentKey();
if (!preg_match('/^\d{4}-\d{2}-H[12]$/', $monthYear)) {
    Response::error('Invalid period. Expected a cutoff key like 2026-08-H1.', 400);
}
$department = isset($_GET['department']) ? trim($_GET['department']) : '';

try {
    $budgetModel = new Budget();
    $departments = $budgetModel->getAllDepartments();
    $departmentStatuses = $budgetModel->getAllDepartmentsStatus($monthYear);
    $nearLimit = $budgetModel->getDepartmentsNearLimit($monthYear, 80.0);

    if ($department === '' && !empty($departments)) {
        $department = $departments[0];
    }

    $selected = $department !== '' ? $budgetModel->getBudgetStatus($department, $monthYear) : null;

    // Real usage by requisition for the selected department/month — every
    // requisition actually booked against that period, with its real payment
    // status. No fabricated rows: an empty result just means none exist yet.
    $usage = [];
    if ($department !== '') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT r.id, r.requisition_number, r.order_date, r.total, r.status,
                   s.company_name,
                   pr.status as payment_request_status
            FROM store_requisitions r
            JOIN suppliers s ON r.supplier_id = s.id
            LEFT JOIN payment_requests pr ON pr.requisition_id = r.id
            WHERE r.department = ? AND r.budget_month_year = ?
            ORDER BY r.order_date DESC
        ");
        $stmt->execute([$department, $monthYear]);
        $usage = $stmt->fetchAll();
    }

    Response::success([
        'departments' => $departments,
        'department_statuses' => $departmentStatuses,
        'departments_near_limit' => $nearLimit,
        'selected_department' => $department,
        'month_year' => $monthYear,
        'selected' => $selected,
        'usage' => $usage,
        'generated_at' => date('Y-m-d H:i:s')
    ], 'Budget fetched');

} catch (Exception $e) {
    error_log('finance/head/get_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
