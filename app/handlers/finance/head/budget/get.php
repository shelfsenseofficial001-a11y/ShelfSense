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

if (!function_exists('budget_overview_build_data')) {
/**
 * Builds the budget overview for a period. Shared by the API endpoint
 * (for period switching) and the Finance Staff/Head Budget pages' first
 * paint (both, since this one handler already serves both roles).
 */
function budget_overview_build_data(Budget $budgetModel, string $periodKey): array {
    $departments = $budgetModel->getAllDepartments();
    $statuses = $budgetModel->getAllDepartmentsStatus($periodKey);
    $nearLimit = $budgetModel->getDepartmentsNearLimit($periodKey);

    return [
        'period_key' => $periodKey,
        'period' => CutoffPeriod::describeKey($periodKey),
        'departments' => $departments,
        'statuses' => $statuses,
        'near_limit' => $nearLimit,
        'recent_periods' => CutoffPeriod::getRecentHalves(),
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }
    if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied.');
    }

    $periodKey = $_GET['period_key'] ?? CutoffPeriod::getCurrentKey();

    try {
        Response::success(budget_overview_build_data(new Budget(), $periodKey), 'Budget overview fetched successfully');
    } catch (Exception $e) {
        error_log('budget/get.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
