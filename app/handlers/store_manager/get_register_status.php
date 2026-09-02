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

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

try {
    $registerModel = new Register();

    $storeManagerId = Auth::userId();
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

    Response::success(['registers' => $result], 'Register status fetched');

} catch (Exception $e) {
    error_log('get_register_status.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
