<?php
// app/handlers/supplier/purchase_orders/respond.php
// Supplier accepts a dispatched PO as-is, or submits a counter-proposal for
// the Store Manager to accept/reject. Price is fixed at PO creation time --
// the supplier can only propose revised quantity/delivery date (e.g. "only
// 3 in stock right now"), never a different unit price.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/PoCounterProposal.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoCounterProposal;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$items = isset($input['items']) ? $input['items'] : [];

if ($poId <= 0 || !in_array($action, ['accept', 'counter_propose'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'counter_propose' && (empty($items) || !is_array($items))) {
    Response::error('At least one line item change is required for a counter-proposal', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([Auth::userId()]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        Response::forbidden('No supplier profile linked to this account.');
    }

    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND supplier_id = ? FOR UPDATE");
    $stmt->execute([$poId, $supplier['id']]);
    $po = $stmt->fetch();
    if (!$po) {
        $db->rollBack();
        Response::notFound('Purchase order not found for this supplier.');
    }
    if ($po['status'] !== 'pending_confirmation') {
        $db->rollBack();
        Response::error('This purchase order is not awaiting your response. Current status: ' . $po['status'], 400);
    }

    $poModel = new PurchaseOrder();
    $eventModel = new PoEvent();
    $requisitionOwner = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $requisitionOwner->execute([$po['requisition_id']]);
    $req = $requisitionOwner->fetch();

    if ($action === 'accept') {
        $poModel->updateStatus($poId, 'confirmed');
        $eventModel->log($poId, 'po_confirmed', 'Supplier accepted the purchase order as-is.', 'all', Auth::userId());
        createNotification(
            $req['requested_by'],
            'po_confirmed',
            "Supplier confirmed Purchase Order {$po['po_number']} for requisition #{$req['requisition_number']}.",
            "?page=store_manager_requisitions"
        );
        $db->commit();
        Response::success(['po_id' => $poId, 'status' => 'confirmed'], 'Purchase order accepted.');
    } else {
        // Quantity/delivery-date changes only -- price is never accepted from the supplier here.
        $quantityOnlyItems = array_map(function ($item) {
            return [
                'po_item_id' => $item['po_item_id'] ?? null,
                'proposed_quantity' => isset($item['proposed_quantity']) ? (int)$item['proposed_quantity'] : null,
                'proposed_unit_price' => null,
                'proposed_delivery_date' => $item['proposed_delivery_date'] ?? null,
                'notes' => $item['notes'] ?? null,
            ];
        }, $items);

        $proposalModel = new PoCounterProposal();
        $proposalId = $proposalModel->create($poId, Auth::userId(), $reason, $quantityOnlyItems);
        $poModel->updateStatus($poId, 'supplier_counter_proposed');
        $eventModel->log($poId, 'po_counter_proposed', 'Supplier proposed a quantity/date change.' . ($reason ? " Reason: $reason" : ''), 'all', Auth::userId());

        createNotification(
            $req['requested_by'],
            'po_counter_proposed',
            "Supplier proposed changes to Purchase Order {$po['po_number']} for requisition #{$req['requisition_number']}.",
            "?page=store_manager_requisitions"
        );
        $db->commit();
        Response::success(['po_id' => $poId, 'status' => 'supplier_counter_proposed', 'proposal_id' => $proposalId], 'Counter-proposal submitted.');
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('purchase_orders/respond.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
