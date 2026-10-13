<?php
// app/handlers/store_manager/goods_receipts/create.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/GoodsReceipt.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../models/PoDiscrepancy.php';
require_once __DIR__ . '/../../../models/PoExcessItem.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\PoEvent;
use App\Models\PoDiscrepancy;
use App\Models\PoExcessItem;

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
    if (!in_array($item['condition'] ?? 'good', ['good', 'damaged', 'missing', 'excess'], true)) {
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
    // Goods Receipt only happens after the supplier has shipped -- shipping
    // happens right after PO confirmation now, well before any payment step.
    // 'discrepancy_logged' is also allowed: a follow-up receipt is exactly
    // how an RMA replacement or a backordered shortfall gets logged.
    if (!in_array($po['status'], ['shipped', 'partially_received', 'discrepancy_logged'], true)) {
        Response::error('This purchase order has not been shipped yet. Current status: ' . $po['status'], 400);
    }

    // Once a line already has an unresolved discrepancy on file, a further
    // receipt against it is the RMA replacement or the backordered
    // shortfall arriving -- that can only be logged as 'good'. Damaged/
    // missing on that same line is tracked by the existing RMA/Backorder
    // record, not re-raised here.
    $discModelPreCheck = new PoDiscrepancy();
    $outstandingByItem = $discModelPreCheck->getOutstandingQuantities($poId);
    foreach ($items as $item) {
        $condition = $item['condition'] ?? 'good';
        if (in_array($condition, ['damaged', 'missing'], true) && !empty($outstandingByItem[(int)$item['po_item_id']])) {
            Response::error('This item already has an unresolved RMA/Backorder -- log the replacement as Good, not Damaged/Missing.', 400);
        }
    }

    // Mirrors the client's "Remaining" ceiling server-side -- the client
    // already blocks over-submitting, but that's a UI nicety, not a guard.
    // Without this, a direct API call (or a client bug) can log arbitrarily
    // more than was ordered, silently inflating stock_quantity and letting
    // an inflated billedQty sail through the 3-way match's good-received check.
    $poItemsPreCheck = (new PurchaseOrder())->getItems($poId);
    $orderedByItem = [];
    foreach ($poItemsPreCheck as $poItem) {
        $orderedByItem[(int)$poItem['id']] = (int)$poItem['quantity'];
    }
    $receivedByItem = (new PurchaseOrder())->getReceivedQuantities($poId);
    $requestedTotals = [];
    foreach ($items as $item) {
        // 'excess' is explicitly beyond the ordered quantity -- it's exempt
        // from this cap by definition, not something to validate against it.
        if (($item['condition'] ?? 'good') === 'excess') {
            continue;
        }
        $itemId = (int)$item['po_item_id'];
        $requestedTotals[$itemId] = ($requestedTotals[$itemId] ?? 0) + intval($item['quantity_received'] ?? 0);
    }
    foreach ($requestedTotals as $itemId => $requestedQty) {
        $ordered = $orderedByItem[$itemId] ?? null;
        if ($ordered === null) {
            Response::error("Item #$itemId does not belong to this purchase order", 400);
        }
        $alreadyReceived = $receivedByItem[$itemId] ?? 0;
        $remaining = max($ordered - $alreadyReceived, 0) + ($outstandingByItem[$itemId] ?? 0);
        if ($requestedQty > $remaining) {
            Response::error("Good + Damaged + Missing for item #$itemId can't exceed the remaining quantity ($remaining)", 400);
        }
    }

    $grModel = new GoodsReceipt();
    $grResult = $grModel->create($poId, Auth::userId(), $receiptDate, $notes, $items);
    $grId = $grResult['gr_id'];
    $grNumber = $grResult['gr_number'];

    // Flag any damaged/missing line as a discrepancy, and close out any
    // outstanding RMA (damaged) or Backorder (missing) discrepancy a 'good'
    // line is the replacement/shortfall-fulfillment for.
    $discModel = new PoDiscrepancy();
    $newDiscrepancies = [];
    foreach ($grResult['items'] as $insertedItem) {
        if ($insertedItem['condition'] === 'good') {
            foreach (['damaged', 'missing'] as $openType) {
                $open = $discModel->getOpenForPoItem($insertedItem['po_item_id'], $openType);
                if ($open) {
                    $discModel->markResolved($open['id'], $insertedItem['goods_receipt_item_id']);
                }
            }
            continue;
        }
        if ($insertedItem['condition'] === 'excess') {
            continue;
        }
        if ($insertedItem['quantity_received'] > 0) {
            $discModel->create([
                'po_id' => $poId,
                'po_item_id' => $insertedItem['po_item_id'],
                'goods_receipt_item_id' => $insertedItem['goods_receipt_item_id'],
                'type' => $insertedItem['condition'],
                'quantity' => $insertedItem['quantity_received'],
            ]);
            $newDiscrepancies[] = $insertedItem;
        }
    }

    // Beyond-ordered-quantity lines: logged for visibility, stays 'open'
    // until the Store Manager accepts it into inventory or returns it.
    $excessModel = new PoExcessItem();
    $newExcessItems = [];
    foreach ($grResult['items'] as $insertedItem) {
        if ($insertedItem['condition'] === 'excess' && $insertedItem['quantity_received'] > 0) {
            $excessModel->create([
                'po_id' => $poId,
                'po_item_id' => $insertedItem['po_item_id'],
                'goods_receipt_item_id' => $insertedItem['goods_receipt_item_id'],
                'quantity' => $insertedItem['quantity_received'],
            ]);
            $newExcessItems[] = $insertedItem;
        }
    }

    $poModel = new PurchaseOrder();
    $fullyReceivedByQty = $poModel->determineReceivedStatus($poId) === 'received';
    if ($discModel->hasBlockingDiscrepancy($poId)) {
        $newStatus = 'discrepancy_logged';
    } else {
        $newStatus = $fullyReceivedByQty ? 'received' : 'partially_received';
    }
    $poModel->updateStatus($poId, $newStatus);

    (new PoEvent())->log(
        $poId,
        'goods_received',
        "{$grNumber}: " . ($fullyReceivedByQty ? 'Goods fully received' : 'Goods partially received') . " by Store Manager.",
        'not_supplier',
        Auth::userId()
    );

    if (!empty($newDiscrepancies)) {
        $productNames = [];
        foreach ($poModel->getItems($poId) as $poItem) {
            $productNames[(int)$poItem['id']] = $poItem['store_product_name'];
        }
        $lines = array_map(function ($i) use ($productNames) {
            $name = $productNames[(int)$i['po_item_id']] ?? "item #{$i['po_item_id']}";
            return "{$i['quantity_received']}x {$name} ({$i['condition']})";
        }, $newDiscrepancies);
        (new PoEvent())->log(
            $poId,
            'discrepancy_logged',
            "Discrepancy logged on {$grNumber}: " . implode(', ', $lines) . '. Supplier action required before this PO can be invoiced.',
            'all',
            Auth::userId()
        );
    }

    if (!empty($newExcessItems)) {
        $productNames = [];
        foreach ($poModel->getItems($poId) as $poItem) {
            $productNames[(int)$poItem['id']] = $poItem['store_product_name'];
        }
        $lines = array_map(function ($i) use ($productNames) {
            $name = $productNames[(int)$i['po_item_id']] ?? "item #{$i['po_item_id']}";
            return "{$i['quantity_received']}x {$name}";
        }, $newExcessItems);
        (new PoEvent())->log(
            $poId,
            'excess_received',
            "Received beyond ordered quantity on {$grNumber}: " . implode(', ', $lines) . '. Awaiting Store Manager decision to accept into inventory or return to supplier.',
            'not_supplier',
            Auth::userId()
        );
    }

    $stmt = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $stmt->execute([$po['requisition_id']]);
    $req = $stmt->fetch();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'goods_received',
            ($fullyReceivedByQty ? 'Goods fully received' : 'Goods partially received') . " for PO {$po['po_number']} (requisition #{$req['requisition_number']}).",
            "?page=finance_staff_payment_requests"
        );
    }

    // Supplier never sees the GR record itself, but they should always know
    // their shipment was received -- not just when something's wrong with it.
    $stmt = $db->prepare("SELECT user_id FROM suppliers WHERE id = ?");
    $stmt->execute([$po['supplier_id']]);
    $supplierUserId = $stmt->fetchColumn();
    if ($supplierUserId) {
        createNotification(
            $supplierUserId,
            'goods_received',
            "{$grNumber}: " . ($fullyReceivedByQty ? 'Your shipment was fully received' : 'Part of your shipment was received') . " for PO {$po['po_number']}.",
            "?page=supplier_requisitions"
        );
        if (!empty($newDiscrepancies)) {
            createNotification(
                $supplierUserId,
                'discrepancy_logged',
                "PO {$po['po_number']} has damaged/missing items -- resolve them to continue invoicing.",
                "?page=supplier_requisitions"
            );
        }
    }

    Response::success([
        'goods_receipt_id' => $grId,
        'gr_number' => $grNumber,
        'po_status' => $newStatus,
    ], "Goods receipt {$grNumber} logged successfully");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('goods_receipts/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
