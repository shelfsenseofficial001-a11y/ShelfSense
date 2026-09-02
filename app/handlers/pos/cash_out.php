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

if (!Auth::posCheck()) {
    Response::unauthorized('Please log in to a register first.');
}

$registerId = Auth::posRegisterId();
$cashierId = Auth::posCashierId(); // whoever's currently picked closes the drawer, recorded for the audit trail

try {
    $registerModel = new Register();
    $allocation = $registerModel->getActiveAllocation($registerId);

    if (!$allocation) {
        Response::error('No active budget to cash out', 400);
    }

    $result = $registerModel->cashOut($allocation['id'], $registerId, $cashierId);

    Response::success(['allocation' => $result], 'Cashed out successfully');

} catch (Exception $e) {
    error_log('cash_out.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
