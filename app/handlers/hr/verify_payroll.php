<?php
// app/handlers/hr/verify_payroll.php

require_once __DIR__ . '/../../models/PayrollCycle.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollCycle;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

// Only Finance Head, SuperAdmin can verify
if (!in_array(Auth::role(), ['finance_head', 'owner']) && !Auth::isSuperAdmin()) {
    Response::unauthorized('Only Finance Head can verify.');
}

$input = json_decode(file_get_contents('php://input'), true);
$cycleId = intval($input['cycle_id'] ?? 0);
$action = $input['action'] ?? 'verify';
$reason = $input['reason'] ?? '';

if ($cycleId <= 0) {
    Response::error('Invalid cycle ID', 400);
}

try {
    $cycleModel = new PayrollCycle();
    $cycle = $cycleModel->get($cycleId);
    if (!$cycle) Response::notFound('Cycle not found');
    if ($cycle['status'] !== 'approved') {
        Response::error('Cycle must be approved first.', 400);
    }

    if ($action === 'verify') {
        $result = $cycleModel->updateStatus($cycleId, 'verified', Auth::userId());
        $cycleModel->addLog($cycleId, 'verify', Auth::userId(), 'Payroll verified');
        $message = 'Payroll verified.';
        $status = 'verified';
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            Response::error('Rejection reason is required.', 400);
        }
        $stmt = \App\Core\Database::getInstance()->getConnection()->prepare("
            UPDATE payroll_cycles SET status = 'draft', rejection_reason = ?, updated_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$reason, $cycleId]);
        $cycleModel->addLog($cycleId, 'reject', Auth::userId(), 'Rejected: ' . $reason);
        $message = 'Payroll rejected.';
        $status = 'draft';
    } else {
        Response::error('Invalid action', 400);
    }

    Response::success([
        'cycle_id' => $cycleId,
        'status' => $status,
        'message' => $message
    ], $message);
} catch (Exception $e) {
    error_log('verify_payroll.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}