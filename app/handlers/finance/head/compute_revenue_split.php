<?php
// app/handlers/finance/head/compute_revenue_split.php

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
$year = isset($input['year']) ? intval($input['year']) : 0;
$month = isset($input['month']) ? intval($input['month']) : 0;
$half = isset($input['half']) ? intval($input['half']) : 0;

if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100 || !in_array($half, [1, 2])) {
    Response::error('Invalid period', 400);
}

try {
    $model = new RevenueSplit();
    $halves = $model->getHalves($year, $month);
    $period = $halves[$half - 1];

    $split = $model->computeDraft($period['start_date'], $period['end_date'], $period['label'], $period['key'], Auth::userId());

    Response::success(['split' => $split], 'Revenue split computed');
} catch (Exception $e) {
    error_log('compute_revenue_split.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
