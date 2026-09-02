<?php
// app/handlers/owner/get_pos_accounts.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Register;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

try {
    $registerModel = new Register();
    Response::success([
        'registers' => $registerModel->getAllWithStoreManagers(),
        'available_store_managers' => $registerModel->getAllStoreManagers()
    ], 'POS accounts fetched');
} catch (Exception $e) {
    error_log('get_pos_accounts.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
