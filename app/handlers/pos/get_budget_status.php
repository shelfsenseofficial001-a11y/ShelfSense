<?php
// app/handlers/pos/get_budget_status.php

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

try {
    $registerModel = new Register();

    $allocation = $registerModel->getActiveAllocation(Auth::posRegisterId());
    $liveSales = null;
    if ($allocation) {
        $liveSales = $registerModel->getLiveSalesForAllocation($allocation['id']);
    }

    Response::success([
        'allocation' => $allocation ?: null,
        'live_sales' => $liveSales
    ], 'Budget status fetched');

} catch (Exception $e) {
    error_log('get_budget_status.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
