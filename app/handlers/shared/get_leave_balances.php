<?php
// app/handlers/shared/get_leave_balances.php

require_once __DIR__ . '/../../models/Leave.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Leave;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : Auth::userId();

// Users can view their own balances; only Department Heads, SuperAdmin can view others
if ($userId != Auth::userId() && !Auth::canApprove() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied');
}

try {
    $leaveModel = new Leave();
    $balances = $leaveModel->getBalances($userId);

    Response::success([
        'balances' => $balances,
        'user_id' => $userId
    ], 'Leave balances fetched successfully');

} catch (Exception $e) {
    error_log('get_leave_balances.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}