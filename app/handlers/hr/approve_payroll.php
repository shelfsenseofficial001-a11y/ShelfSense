<?php
// app/handlers/hr/approve_payroll.php

require_once __DIR__ . '/../../models/PayrollCycle.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollCycle;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only HR Head, SuperAdmin, and Head HR Trainee can approve
$targetRole = Auth::getNormalizedTargetRole();
$isHeadHrTrainee = Auth::isTrainee() && $targetRole === 'hr_head';

if (!Auth::canApprove() && !Auth::isHRHead() && !$isHeadHrTrainee) {
    Response::forbidden('Only HR Head can approve payroll.');
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
    if ($cycle['status'] !== 'pending_approval') {
        Response::error('Payroll cycle is not pending approval.', 400);
    }

    $result = $cycleModel->updateStatus($cycleId, 'approved', Auth::userId());
    if ($result) {
        $cycleModel->addLog($cycleId, 'approve', Auth::userId(), 'Payroll approved by HR Head');
        Response::success([
            'cycle_id' => $cycleId,
            'status' => 'approved'
        ], 'Payroll approved successfully.');
    } else {
        Response::error('Failed to approve payroll.', 500);
    }
} catch (Exception $e) {
    error_log('approve_payroll.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}