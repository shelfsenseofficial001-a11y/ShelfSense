<?php
// app/handlers/hr/process_payroll.php

require_once __DIR__ . '/../../models/PayrollCycle.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollCycle;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only HR, Finance, or SuperAdmin can process
if (!Auth::canAccessModule('hr_head') && !in_array(Auth::role(), ['finance_head', 'owner']) && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$input = json_decode(file_get_contents('php://input'), true);
$cycleId = isset($input['cycle_id']) ? intval($input['cycle_id']) : 0;

if ($cycleId <= 0) {
    Response::error('Invalid cycle ID', 400);
}

try {
    $cycleModel = new PayrollCycle();
    $cycle = $cycleModel->get($cycleId);
    if (!$cycle) {
        Response::notFound('Payroll cycle not found');
    }
    if ($cycle['status'] !== 'verified') {
        Response::error('Payroll cycle must be verified before processing.', 400);
    }

    $result = $cycleModel->updateStatus($cycleId, 'processed', Auth::userId());
    if ($result) {
        $cycleModel->addLog($cycleId, 'process', Auth::userId(), 'Payroll processed');
        Response::success([
            'cycle_id' => $cycleId,
            'status' => 'processed'
        ], 'Payroll processed successfully.');
    } else {
        Response::error('Failed to process payroll.', 500);
    }
} catch (Exception $e) {
    error_log('process_payroll.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}