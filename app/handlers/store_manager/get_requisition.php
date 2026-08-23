<?php
// app/handlers/store_manager/get_requisition.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/StoreRequisition.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\StoreRequisition;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error('Invalid requisition ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $requisitionModel = new StoreRequisition();
    // getWithGoodsReceipt() already chains items + invoice + goods receipt (with received items)
    $requisition = $requisitionModel->getWithGoodsReceipt($id);

    if (!$requisition) {
        Response::notFound('Requisition not found');
    }

    // Payment request (if any) — used for the "Payment Requested" / "Finance Approved" timeline entries
    $stmt = $db->prepare("
        SELECT id, status, requested_at, approved_at, rejection_reason
        FROM payment_requests
        WHERE requisition_id = ?
        ORDER BY requested_at DESC
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $requisition['payment_request'] = $stmt->fetch() ?: null;

    Response::success([
        'requisition' => $requisition
    ], 'Requisition details fetched successfully');

} catch (Exception $e) {
    error_log('get_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
