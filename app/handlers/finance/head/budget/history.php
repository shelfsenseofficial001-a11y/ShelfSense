<?php
// app/handlers/finance/head/budget/history.php

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
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

$filters = [];
if (!empty($_GET['department_id'])) {
    $filters['department_id'] = intval($_GET['department_id']);
}
if (!empty($_GET['period_key'])) {
    $filters['period_key'] = $_GET['period_key'];
}
if (!empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}

try {
    $budgetModel = new Budget();
    $history = $budgetModel->getTransactionHistory($filters, $limit, $offset);
    $total = $budgetModel->getTransactionHistoryCount($filters);

    Response::success([
        'transactions' => $history,
        'pagination' => [
            'currentPage' => $page,
            'perPage' => $limit,
            'totalRecords' => $total,
            'totalPages' => (int)ceil($total / $limit),
        ],
    ], 'Transaction history fetched successfully');
} catch (Exception $e) {
    error_log('budget/history.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
