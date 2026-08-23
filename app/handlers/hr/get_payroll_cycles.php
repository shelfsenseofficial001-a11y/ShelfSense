<?php
// app/handlers/hr/get_payroll_cycles.php

require_once __DIR__ . '/../../models/PayrollCycle.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollCycle;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$status = isset($_GET['status']) ? trim($_GET['status']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

$filters = [];
if ($status && $status !== 'all') $filters['status'] = $status;
if ($year) $filters['year'] = $year;
if ($month) $filters['month'] = $month;

try {
    $cycleModel = new PayrollCycle();
    $cycles = $cycleModel->getAll($filters);
    
    Response::success([
        'cycles' => $cycles,
        'filters' => $filters
    ], 'Payroll cycles fetched successfully');
} catch (Exception $e) {
    error_log('get_payroll_cycles.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}