<?php
// app/handlers/finance/head/budget_requests/list.php

require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/BudgetRequest.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\BudgetRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$status = isset($_GET['status']) ? trim($_GET['status']) : 'pending';

try {
    $filters = $status !== '' ? ['status' => $status] : [];
    $requests = (new BudgetRequest())->getAll($filters);
    Response::success(['requests' => $requests], 'Budget requests fetched successfully');
} catch (Exception $e) {
    error_log('finance/head/budget_requests/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
