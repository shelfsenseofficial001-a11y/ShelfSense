<?php
// app/handlers/store_manager/get_register_status.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Register;

if (!function_exists('sm_register_status_build_data')) {
/**
 * Builds each register's status (active allocation + live sales) for a
 * store manager. Shared by the API endpoint (for refresh) and the Budget
 * page's first paint.
 */
function sm_register_status_build_data(Register $registerModel, int $storeManagerId): array {
    $registers = $registerModel->getAllForStoreManager($storeManagerId);

    $result = [];
    foreach ($registers as $register) {
        $activeAllocation = $registerModel->getActiveAllocation($register['id']);
        $liveSales = $activeAllocation ? $registerModel->getLiveSalesForAllocation($activeAllocation['id']) : null;

        $result[] = [
            'register' => $register,
            'active_allocation' => $activeAllocation ?: null,
            'live_sales' => $liveSales
        ];
    }

    return ['registers' => $result];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }

    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Store Manager role required.');
    }

    try {
        Response::success(sm_register_status_build_data(new Register(), Auth::userId()), 'Register status fetched');
    } catch (Exception $e) {
        error_log('get_register_status.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
