<?php
// app/handlers/finance/head/get_budget_adjustments.php
// Paginated, filterable allocation-adjustment audit trail.

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

$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$monthYear = isset($_GET['month']) ? trim($_GET['month']) : '';

try {
    $budgetModel = new Budget();
    $filters = [];
    if ($department !== '') $filters['department'] = $department;
    if ($monthYear !== '') $filters['month_year'] = $monthYear;

    $total = $budgetModel->getAdjustmentHistoryCount($filters);
    $offset = ($page - 1) * $limit;
    $rows = $budgetModel->getAdjustmentHistory($filters, $limit, $offset);

    Response::success([
        'adjustments' => $rows,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => $total,
            'totalPages' => (int)ceil($total / $limit)
        ]
    ], 'Budget adjustment history fetched');

} catch (Exception $e) {
    error_log('finance/head/get_budget_adjustments.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
