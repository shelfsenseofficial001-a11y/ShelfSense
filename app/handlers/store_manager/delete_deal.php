<?php
// app/handlers/store_manager/delete_deal.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Deal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Deal;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$dealId = isset($input['id']) ? intval($input['id']) : 0;

if ($dealId <= 0) {
    Response::error('Deal ID is required', 400);
}

try {
    $dealModel = new Deal();
    if (!$dealModel->getById($dealId)) {
        Response::notFound('Deal not found');
    }

    // Past receipts already snapshot the deal's name/price in
    // order_deal_items, so removing the deal itself (rather than just
    // deactivating it) doesn't touch sales history -- it just stops it
    // from being sellable at POS going forward.
    $dealModel->delete($dealId);

    Response::success([], 'Deal removed');
} catch (Exception $e) {
    error_log('delete_deal.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
