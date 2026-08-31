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
    $db = Database::getInstance()->getConnection();
    $registerModel = new Register();

    $storeManagerId = Auth::userId();
    $register = $registerModel->getOrCreateForStoreManager($storeManagerId);
    $activeAllocation = $registerModel->getActiveAllocation($register['id']);

    $liveSales = null;
    if ($activeAllocation) {
        $liveSales = $registerModel->getLiveSalesForAllocation($activeAllocation['id']);
    }

    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, employee_number
        FROM users
        WHERE role = 'employee' AND is_active = 1
        ORDER BY first_name ASC
    ");
    $stmt->execute();
    $cashiers = $stmt->fetchAll();

    Response::success([
        'register' => $register,
        'active_allocation' => $activeAllocation ?: null,
        'live_sales' => $liveSales,
        'cashiers' => $cashiers
    ], 'Register status fetched');

} catch (Exception $e) {
    error_log('get_register_status.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
