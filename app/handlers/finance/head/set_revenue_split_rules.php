<?php
// app/handlers/finance/head/set_revenue_split_rules.php

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
$rules = $input['rules'] ?? null;

if (!is_array($rules) || empty($rules)) {
    Response::error('At least one department rule is required.', 400);
}

try {
    $model = new RevenueSplit();
    $updated = $model->saveRules($rules, Auth::userId());
    Response::success(['rules' => $updated], 'Revenue split rules saved');
} catch (Exception $e) {
    error_log('set_revenue_split_rules.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
