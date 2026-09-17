<?php
// app/handlers/store_manager/toggle_deal.php

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
$isActive = isset($input['is_active']) ? intval($input['is_active']) : null;

if ($dealId <= 0 || $isActive === null) {
    Response::error('Deal ID and status are required', 400);
}

try {
    $dealModel = new Deal();
    if (!$dealModel->getById($dealId)) {
        Response::notFound('Deal not found');
    }

    $dealModel->setActive($dealId, $isActive);

    Response::success(['deal' => $dealModel->getById($dealId)], $isActive ? 'Deal activated' : 'Deal paused');
} catch (Exception $e) {
    error_log('toggle_deal.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
