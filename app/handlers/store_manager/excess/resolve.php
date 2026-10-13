<?php
// app/handlers/store_manager/excess/resolve.php
// Store Manager decides what happens to units received beyond the PO's
// ordered quantity: accept them into sellable stock at an agreed price, or
// have the supplier take them back. Never auto-applied on receipt -- see
// goods_receipts/create.php and PoExcessItem.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PoExcessItem.php';
require_once __DIR__ . '/../../../models/PoEvent.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PoExcessItem;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$excessId = isset($input['excess_id']) ? intval($input['excess_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if ($excessId <= 0 || !in_array($action, ['accept', 'return'], true)) {
    Response::error('Invalid request', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $excessModel = new PoExcessItem();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM po_excess_items WHERE id = ? FOR UPDATE");
    $stmt->execute([$excessId]);
    $excess = $stmt->fetch();
    if (!$excess) {
        $db->rollBack();
        Response::notFound('Excess item not found');
    }
    if ($excess['status'] !== 'open') {
        $db->rollBack();
        Response::error('This excess item has already been resolved.', 400);
    }

    $stmt = $db->prepare("
        SELECT poi.id as po_item_id, p.id as store_product_id, p.name as store_product_name
        FROM purchase_order_items poi
        JOIN products p ON poi.store_product_id = p.id
        WHERE poi.id = ?
    ");
    $stmt->execute([$excess['po_item_id']]);
    $poItem = $stmt->fetch();
    if (!$poItem) {
        $db->rollBack();
        Response::notFound('Purchase order item not found');
    }

    if ($action === 'accept') {
        $unitPrice = isset($input['unit_price']) ? (float)$input['unit_price'] : null;
        if ($unitPrice === null || $unitPrice < 0) {
            $db->rollBack();
            Response::error('A non-negative unit price is required to accept excess stock.', 400);
        }
        $excessModel->markAccepted($excessId, $unitPrice, Auth::userId(), $notes);

        $stockUpd = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stockUpd->execute([$excess['quantity'], $poItem['store_product_id']]);

        (new PoEvent())->log(
            $excess['po_id'],
            'excess_accepted',
            "{$excess['quantity']}x {$poItem['store_product_name']} (excess) accepted into inventory at ₱" . number_format($unitPrice, 2) . ' each.' . ($notes ? " Note: $notes" : ''),
            'not_supplier',
            Auth::userId()
        );
    } else {
        $excessModel->markReturned($excessId, Auth::userId(), $notes);

        (new PoEvent())->log(
            $excess['po_id'],
            'excess_returned',
            "{$excess['quantity']}x {$poItem['store_product_name']} (excess) marked for return to supplier." . ($notes ? " Note: $notes" : ''),
            'not_supplier',
            Auth::userId()
        );
    }

    $db->commit();
    Response::success(['excess_id' => $excessId, 'status' => $action === 'accept' ? 'accepted' : 'returned'], 'Excess item resolved.');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('excess/resolve.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
