<?php
// app/handlers/finance/head/get_revenue_split_rules.php

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
    Response::success(['rules' => $model->getRules()], 'Revenue split rules fetched');
} catch (Exception $e) {
    error_log('get_revenue_split_rules.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
