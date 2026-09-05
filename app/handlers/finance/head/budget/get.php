<?php
// app/handlers/finance/head/budget/get.php

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$periodKey = $_GET['period_key'] ?? CutoffPeriod::getCurrentKey();

try {
    $budgetModel = new Budget();
    $departments = $budgetModel->getAllDepartments();
    $statuses = $budgetModel->getAllDepartmentsStatus($periodKey);
    $nearLimit = $budgetModel->getDepartmentsNearLimit($periodKey);

    Response::success([
        'period_key' => $periodKey,
        'period' => CutoffPeriod::describeKey($periodKey),
        'departments' => $departments,
        'statuses' => $statuses,
        'near_limit' => $nearLimit,
        'recent_periods' => CutoffPeriod::getRecentHalves(),
    ], 'Budget overview fetched successfully');
} catch (Exception $e) {
    error_log('budget/get.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
