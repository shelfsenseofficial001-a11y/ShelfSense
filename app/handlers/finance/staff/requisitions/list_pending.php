<?php
// app/handlers/finance/staff/requisitions/list_pending.php

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Requisition.php';
require_once __DIR__ . '/../../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

try {
    $reqModel = new Requisition();
    $result = $reqModel->getAll($page, $limit, ['status' => 'pending_budget_check']);

    $budgetModel = new Budget();
    foreach ($result['requisitions'] as &$r) {
        $r['budget_status'] = $budgetModel->getBudgetStatus(
            $r['department_id'],
            $r['period_key'],
            (float)$r['subtotal'],
            'requisition',
            $r['id']
        );
    }
    unset($r);

    Response::success($result, 'Pending requisitions fetched successfully');
} catch (Exception $e) {
    error_log('finance/staff/requisitions/list_pending.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
