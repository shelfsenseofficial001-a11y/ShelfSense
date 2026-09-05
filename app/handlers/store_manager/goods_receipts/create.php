<?php
// app/handlers/store_manager/goods_receipts/create.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/GoodsReceipt.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$receiptDate = isset($input['receipt_date']) ? trim($input['receipt_date']) : date('Y-m-d');
$notes = isset($input['notes']) ? trim($input['notes']) : null;
$items = isset($input['items']) ? $input['items'] : [];

if ($poId <= 0 || empty($items) || !is_array($items)) {
    Response::error('Purchase order and at least one item are required', 400);
}
foreach ($items as $item) {
    $qty = intval($item['quantity_received'] ?? -1);
    if (empty($item['po_item_id']) || $qty < 0) {
        Response::error('Each item needs a valid po_item_id and a non-negative quantity_received', 400);
    }
    if (!in_array($item['condition'] ?? 'good', ['good', 'damaged', 'missing'], true)) {
        Response::error('Invalid condition value', 400);
    }
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();
    if (!$po) {
        Response::notFound('Purchase order not found');
    }
    // Goods Receipt only happens after the supplier has shipped -- which itself
    // only happens after payment, per the pay-before-delivery flow.
    if (!in_array($po['status'], ['shipped', 'partially_received'], true)) {
        Response::error('This purchase order has not been shipped yet. Current status: ' . $po['status'], 400);
    }

    $grModel = new GoodsReceipt();
    $grId = $grModel->create($poId, Auth::userId(), $receiptDate, $notes, $items);

    $poModel = new PurchaseOrder();
    $poItems = $poModel->getItems($poId);
    $fullyReceived = true;
    foreach ($poItems as $item) {
        if ((int)$item['received_quantity'] < (int)$item['quantity']) {
            $fullyReceived = false;
            break;
        }
    }
    $poModel->updateStatus($poId, $fullyReceived ? 'received' : 'partially_received');

    (new PoEvent())->log(
        $poId,
        'goods_received',
        ($fullyReceived ? 'Goods fully received' : 'Goods partially received') . " by Store Manager.",
        'not_supplier',
        Auth::userId()
    );

    $stmt = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $stmt->execute([$po['requisition_id']]);
    $req = $stmt->fetch();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'goods_received',
            ($fullyReceived ? 'Goods fully received' : 'Goods partially received') . " for PO {$po['po_number']} (requisition #{$req['requisition_number']}).",
            "?page=finance_staff_payment_requests"
        );
    }

    Response::success([
        'goods_receipt_id' => $grId,
        'po_status' => $fullyReceived ? 'received' : 'partially_received',
    ], 'Goods receipt logged successfully');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('goods_receipts/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
