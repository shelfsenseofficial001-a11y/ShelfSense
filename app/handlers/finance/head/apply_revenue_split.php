<?php
// app/handlers/finance/head/apply_revenue_split.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/RevenueSplit.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\RevenueSplit;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$splitId = isset($input['split_id']) ? intval($input['split_id']) : 0;

if ($splitId <= 0) {
    Response::error('Invalid revenue split', 400);
}

try {
    $model = new RevenueSplit();
    $split = $model->apply($splitId, Auth::userId());
    Response::success(['split' => $split], 'Revenue split applied to department budgets');
} catch (Exception $e) {
    error_log('apply_revenue_split.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
