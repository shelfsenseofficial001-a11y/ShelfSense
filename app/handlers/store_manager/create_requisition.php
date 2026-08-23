<?php
// app/handlers/store_manager/create_requisition.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : 0;
$orderDate = isset($input['order_date']) ? trim($input['order_date']) : '';
$expectedDelivery = isset($input['expected_delivery']) ? trim($input['expected_delivery']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$items = isset($input['items']) ? $input['items'] : [];

if ($supplierId <= 0) {
    Response::error('Supplier is required', 400);
}
if (empty($orderDate)) {
    Response::error('Order date is required', 400);
}
if (empty($items) || !is_array($items)) {
    Response::error('At least one item is required', 400);
}

$expectedDeliveryError = validateExpectedDeliveryDate($expectedDelivery);
if ($expectedDeliveryError) {
    Response::error($expectedDeliveryError, 400, ['expected_delivery' => $expectedDeliveryError]);
}

if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}

foreach ($items as $item) {
    $storeProductId = intval($item['store_product_id'] ?? 0);
    $supplierProductId = intval($item['supplier_product_id'] ?? 0);
    $quantity = intval($item['quantity'] ?? 0);
    $unitPrice = floatval($item['unit_price'] ?? 0);

    if ($storeProductId <= 0 || $supplierProductId <= 0 || $quantity <= 0 || $unitPrice <= 0) {
        Response::error('Invalid item: store product, supplier product, quantity, and price are required', 400);
    }
    if ($quantity > 999) {
        Response::error('Quantity cannot exceed 999 per item', 400);
    }
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM store_requisitions WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = $stmt->fetch()['count'] + 1;
    $requisitionNumber = 'REQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("
        INSERT INTO store_requisitions (
            requisition_number, created_by, supplier_id, order_date, expected_delivery, notes, budget_month_year, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_supplier')
    ");
    $stmt->execute([
        $requisitionNumber,
        Auth::userId(),
        $supplierId,
        $orderDate,
        $expectedDelivery !== '' ? $expectedDelivery : null,
        $notes,
        date('Y-m')
    ]);
    $requisitionId = $db->lastInsertId();

    $subtotal = 0;
    $stmt = $db->prepare("
        INSERT INTO store_requisition_items (
            requisition_id, store_product_id, supplier_product_id, quantity, unit_price, total
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $storeProductId = intval($item['store_product_id']);
        $supplierProductId = intval($item['supplier_product_id']);
        $quantity = intval($item['quantity']);
        $unitPrice = floatval($item['unit_price']);
        $total = $quantity * $unitPrice;
        $subtotal += $total;
        $stmt->execute([$requisitionId, $storeProductId, $supplierProductId, $quantity, $unitPrice, $total]);
    }

    $stmt = $db->prepare("
        UPDATE store_requisitions SET subtotal = ?, total = ? WHERE id = ?
    ");
    $stmt->execute([$subtotal, $subtotal, $requisitionId]);

    $db->commit();

    Response::success([
        'requisition_id' => $requisitionId,
        'requisition_number' => $requisitionNumber,
        'total' => $subtotal
    ], 'Requisition created successfully and sent to supplier');

} catch (Exception $e) {
    $db->rollBack();
    error_log('create_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}