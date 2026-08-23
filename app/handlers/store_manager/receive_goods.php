<?php
// app/handlers/store_manager/receive_goods.php

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
$id = isset($input['id']) ? intval($input['id']) : 0;
$receivedItems = isset($input['received_items']) ? $input['received_items'] : [];

if ($id <= 0) {
    Response::error('Invalid requisition ID', 400);
}
if (empty($receivedItems)) {
    Response::error('At least one item must be received', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT status, supplier_id FROM store_requisitions WHERE id = ?");
    $stmt->execute([$id]);
    $req = $stmt->fetch();
    if (!$req) {
        Response::notFound('Requisition not found');
    }

    // ✅ Allow receiving when status is shipped, paid, finance_approved, or awaiting_finance_staff
    $allowedStatuses = ['awaiting_finance_staff', 'finance_approved', 'paid', 'shipped'];
    if (!in_array($req['status'], $allowedStatuses)) {
        Response::error('Cannot receive goods. Current status: ' . $req['status']);
    }

    $stmt = $db->prepare("
        INSERT INTO goods_receipts (requisition_id, received_by, receipt_date, status)
        VALUES (?, ?, NOW(), 'draft')
    ");
    $stmt->execute([$id, Auth::userId()]);
    $receiptId = $db->lastInsertId();

    foreach ($receivedItems as $ri) {
        $requisitionItemId = intval($ri['requisition_item_id']);
        $quantityReceived = intval($ri['quantity_received']);
        if ($quantityReceived <= 0) continue;

        $stmt = $db->prepare("
            SELECT ri.store_product_id, ri.quantity as ordered_quantity
            FROM store_requisition_items ri
            WHERE ri.id = ?
        ");
        $stmt->execute([$requisitionItemId]);
        $item = $stmt->fetch();
        if (!$item) continue;

        $stmt = $db->prepare("
            UPDATE store_requisition_items SET received_quantity = received_quantity + ? WHERE id = ?
        ");
        $stmt->execute([$quantityReceived, $requisitionItemId]);

        $stmt = $db->prepare("
            INSERT INTO goods_receipt_items (goods_receipt_id, requisition_item_id, quantity_received)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$receiptId, $requisitionItemId, $quantityReceived]);

        // ✅ Update stock
        $stmt = $db->prepare("
            UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?
        ");
        $stmt->execute([$quantityReceived, $item['store_product_id']]);
    }

    $stmt = $db->prepare("UPDATE goods_receipts SET status = 'completed' WHERE id = ?");
    $stmt->execute([$receiptId]);

    // Check if all items received
    $stmt = $db->prepare("
        SELECT COUNT(*) as incomplete 
        FROM store_requisition_items 
        WHERE requisition_id = ? AND received_quantity < quantity
    ");
    $stmt->execute([$id]);
    $incomplete = $stmt->fetchColumn();

    $newStatus = ($incomplete == 0) ? 'completed' : 'partial_received';

    $stmt = $db->prepare("
        UPDATE store_requisitions SET status = ?, updated_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$newStatus, $id]);

    $db->commit();

    Response::success([
        'requisition_id' => $id,
        'receipt_id' => $receiptId,
        'status' => $newStatus
    ], 'Goods received successfully. Inventory updated.');

} catch (Exception $e) {
    $db->rollBack();
    error_log('receive_goods.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}