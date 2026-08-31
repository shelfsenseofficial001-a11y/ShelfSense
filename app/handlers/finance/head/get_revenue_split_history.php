<?php
// app/handlers/finance/head/get_revenue_split_history.php

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

try {
    $model = new RevenueSplit();
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    Response::success(['history' => $model->getHistory($limit)], 'Revenue split history fetched');
} catch (Exception $e) {
    error_log('get_revenue_split_history.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
