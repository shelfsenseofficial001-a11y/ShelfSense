<?php
// app/handlers/hr/cancel_payroll.php

require_once __DIR__ . '/../../models/PayrollCycle.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollCycle;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
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
    $allowed = ['draft', 'pending_approval', 'approved'];
    if (!in_array($cycle['status'], $allowed)) {
        Response::error('This payroll cycle cannot be cancelled. Status: ' . $cycle['status'], 400);
    }

    $result = $cycleModel->updateStatus($cycleId, 'cancelled', Auth::userId());
    if ($result) {
        $cycleModel->addLog($cycleId, 'cancel', Auth::userId(), 'Payroll cancelled');
        Response::success([
            'cycle_id' => $cycleId,
            'status' => 'cancelled'
        ], 'Payroll cycle cancelled successfully.');
    } else {
        Response::error('Failed to cancel payroll.', 500);
    }
} catch (Exception $e) {
    error_log('cancel_payroll.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}