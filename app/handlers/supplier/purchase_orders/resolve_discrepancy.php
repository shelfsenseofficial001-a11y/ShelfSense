<?php
// app/handlers/supplier/purchase_orders/resolve_discrepancy.php
// Supplier resolves one open damaged/missing line from a Goods Receipt.
// 'backorder' (missing items) just needs a new delivery date and stops
// blocking invoicing right away for the quantity already received in good
// condition. 'rma' (damaged items) needs a pickup date but keeps the whole
// PO's invoice path locked until a replacement is physically received and
// re-receipted clean by the Store Manager.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/PoDiscrepancy.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoDiscrepancy;
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
$discrepancyId = isset($input['discrepancy_id']) ? intval($input['discrepancy_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$newDeliveryDate = isset($input['new_delivery_date']) ? trim($input['new_delivery_date']) : '';
$pickupDate = isset($input['pickup_date']) ? trim($input['pickup_date']) : '';

if ($poId <= 0 || $discrepancyId <= 0 || !in_array($action, ['backorder', 'rma'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'backorder' && $newDeliveryDate === '') {
    Response::error('A new delivery date is required to backorder this item.', 400);
}
if ($action === 'rma' && $pickupDate === '') {
    Response::error('A pickup date is required to request an RMA for this item.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $supplierId = Auth::supplierId();
    if (!$supplierId) {
        Response::forbidden('No supplier profile linked to this account.');
    }

    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND supplier_id = ? FOR UPDATE");
    $stmt->execute([$poId, $supplierId]);
    $po = $stmt->fetch();
    if (!$po) {
        $db->rollBack();
        Response::notFound('Purchase order not found for this supplier.');
    }

    $discModel = new PoDiscrepancy();
    $discrepancy = $discModel->getById($discrepancyId);
    if (!$discrepancy || (int)$discrepancy['po_id'] !== $poId) {
        $db->rollBack();
        Response::notFound('Discrepancy not found for this purchase order.');
    }
    if ($discrepancy['status'] !== 'open') {
        $db->rollBack();
        Response::error('This discrepancy has already been actioned.', 400);
    }
    $expectedAction = $discrepancy['type'] === 'missing' ? 'backorder' : 'rma';
    if ($action !== $expectedAction) {
        $db->rollBack();
        Response::error("A {$discrepancy['type']} item must be resolved with the '{$expectedAction}' action.", 400);
    }

    if ($action === 'backorder') {
        $discModel->markActionTaken($discrepancyId, ['new_delivery_date' => $newDeliveryDate]);
        $eventDescription = "Supplier backordered {$discrepancy['quantity']} unit(s), new delivery date {$newDeliveryDate}.";
        $eventType = 'discrepancy_backordered';
    } else {
        $rmaNumber = $discModel->generateRmaNumber();
        $discModel->markActionTaken($discrepancyId, ['pickup_date' => $pickupDate, 'rma_number' => $rmaNumber]);
        $eventDescription = "Supplier requested RMA {$rmaNumber} for {$discrepancy['quantity']} damaged unit(s), pickup date {$pickupDate}.";
        $eventType = 'discrepancy_rma_requested';
    }

    (new PoEvent())->log($poId, $eventType, $eventDescription, 'all', Auth::userId());

    $poModel = new PurchaseOrder();
    if (!$discModel->hasBlockingDiscrepancy($poId)) {
        $newStatus = $poModel->determineReceivedStatus($poId);
        $poModel->updateStatus($poId, $newStatus);
    } else {
        $newStatus = $po['status'];
    }

    $stmt = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $stmt->execute([$po['requisition_id']]);
    $req = $stmt->fetch();

    $notifyMessage = "Supplier " . ($action === 'backorder' ? 'backordered' : 'requested an RMA for') . " PO {$po['po_number']} (requisition #{$req['requisition_number']}).";
    if ($req) {
        createNotification($req['requested_by'], $eventType, $notifyMessage, "?page=store_manager_requisitions");
    }
    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification($u['user_id'], $eventType, $notifyMessage, "?page=finance_staff_payment_requests");
    }

    $db->commit();
    Response::success(['po_id' => $poId, 'discrepancy_id' => $discrepancyId, 'po_status' => $newStatus], 'Discrepancy resolution recorded.');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('purchase_orders/resolve_discrepancy.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
