<?php
// app/handlers/supplier/ship_goods.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$trackingNumber = isset($input['tracking_number']) ? trim($input['tracking_number']) : '';
$shippingNotes = isset($input['notes']) ? trim($input['notes']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid requisition ID', 400);
}
if (strlen($trackingNumber) > 100) {
    Response::error('Tracking number cannot exceed 100 characters', 400);
}
if (strlen($shippingNotes) > 500) {
    Response::error('Shipping notes cannot exceed 500 characters', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    // Get supplier ID from logged-in user
    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    // Check requisition exists and belongs to this supplier
    $stmt = $db->prepare("
        SELECT r.*, u.user_id as store_manager_id
        FROM store_requisitions r
        JOIN users u ON r.created_by = u.user_id
        WHERE r.id = ? AND r.supplier_id = ?
    ");
    $stmt->execute([$requisitionId, $supplierId]);
    $requisition = $stmt->fetch();

    if (!$requisition) {
        Response::notFound('Requisition not found or not assigned to you');
    }

    // ✅ Only allow shipping if status is 'paid'
    if ($requisition['status'] !== 'paid') {
        Response::error('Requisition must be paid before shipping. Current status: ' . $requisition['status']);
    }

    // Shipping details have no dedicated columns (no tracking_number field exists on
    // store_requisitions) — record them in notes so they aren't silently discarded.
    $shipmentNote = trim('[SHIPPED]'
        . ($trackingNumber !== '' ? " Tracking #: {$trackingNumber}." : '')
        . ($shippingNotes !== '' ? " Notes: {$shippingNotes}" : ''));
    $newNotes = trim(($requisition['notes'] ? $requisition['notes'] . "\n" : '') . $shipmentNote);

    // Update status to shipped
    $stmt = $db->prepare("
        UPDATE store_requisitions
        SET status = 'shipped', notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newNotes, $requisitionId]);

    // Notify Store Manager
    $notifyMessage = "Supplier has shipped goods for requisition #{$requisition['requisition_number']}."
        . ($trackingNumber !== '' ? " Tracking #: {$trackingNumber}." : '')
        . " Please receive the goods.";
    createNotification(
        $requisition['store_manager_id'],
        'goods_shipped',
        $notifyMessage,
        "?page=store_manager_requisitions"
    );

    Response::success([
        'requisition_id' => $requisitionId,
        'status' => 'shipped',
        'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null
    ], 'Goods marked as shipped. Store Manager has been notified.');

} catch (Exception $e) {
    error_log('ship_goods.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}