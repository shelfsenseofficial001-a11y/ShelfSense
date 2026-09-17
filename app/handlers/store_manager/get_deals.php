<?php
// app/handlers/store_manager/get_deals.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Deal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Deal;

if (!function_exists('sm_deals_build_data')) {
function sm_deals_build_data(): array {
    $dealModel = new Deal();
    return ['deals' => $dealModel->getAll(false)];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login');
    }
    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Store Manager role required.');
    }

    try {
        Response::success(sm_deals_build_data(), 'Deals fetched successfully');
    } catch (Exception $e) {
        error_log('get_deals.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
