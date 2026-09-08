<?php
// app/handlers/pos/get_budget_status.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Register;

if (!function_exists('pos_budget_status_build_data')) {
/**
 * Builds the current register's active allocation + live sales. Shared
 * by the API endpoint (for refresh/cash-out) and the Budget page's first
 * paint.
 */
function pos_budget_status_build_data(Register $registerModel, int $registerId): array {
    $allocation = $registerModel->getActiveAllocation($registerId);
    $liveSales = null;
    if ($allocation) {
        $liveSales = $registerModel->getLiveSalesForAllocation($allocation['id']);
    }

    return [
        'allocation' => $allocation ?: null,
        'live_sales' => $liveSales
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::posCheck()) {
        Response::unauthorized('Please log in to a register first.');
    }

    try {
        Response::success(pos_budget_status_build_data(new Register(), Auth::posRegisterId()), 'Budget status fetched');
    } catch (Exception $e) {
        error_log('get_budget_status.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
