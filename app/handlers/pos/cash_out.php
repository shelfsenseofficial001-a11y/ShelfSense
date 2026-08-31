<?php
// app/handlers/pos/cash_out.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Register;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isEmployee() && !Auth::isOwner() && !(Auth::isTrainee() && Auth::getTraineeTargetRole() === 'employee')) {
    Response::forbidden('Access denied. Employee role required.');
}

$cashierId = Auth::userId();

try {
    $registerModel = new Register();
    $allocation = $registerModel->getActiveAllocationForCashier($cashierId);

    if (!$allocation) {
        Response::error('No active budget to cash out', 400);
    }

    $result = $registerModel->cashOut($allocation['id'], $cashierId);

    Response::success(['allocation' => $result], 'Cashed out successfully');

} catch (Exception $e) {
    error_log('cash_out.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
