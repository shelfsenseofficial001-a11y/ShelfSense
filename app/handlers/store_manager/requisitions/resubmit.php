<?php
// app/handlers/store_manager/requisitions/resubmit.php
// Store Manager's response to a Finance Head "Request Revision": change the
// suggested supplier and/or items, then resubmit -- goes back through budget
// check from scratch (status -> pending_budget_check) since the items/price
// may have changed. Replaces the requisition's existing items entirely
// (simpler and safer than diffing) and re-runs the same eligible-supplier
// validation as creation.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Requisition.php';
require_once __DIR__ . '/../../../models/RequisitionEvent.php';
require_once __DIR__ . '/../../../models/SupplierProduct.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\RequisitionEvent;
use App\Models\SupplierProduct;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : 0;
$neededByDate = isset($input['needed_by_date']) ? trim($input['needed_by_date']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$supplierNote = isset($input['supplier_note']) ? trim($input['supplier_note']) : '';
$items = isset($input['items']) ? $input['items'] : [];

if ($requisitionId <= 0 || $supplierId <= 0) {
    Response::error('Invalid request', 400);
}
if (empty($items) || !is_array($items)) {
    Response::error('At least one item is required', 400);
}
if (strlen($notes) > 500 || strlen($supplierNote) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}
$neededByError = validateExpectedDeliveryDate($neededByDate);
if ($neededByError) {
    Response::error($neededByError, 400, ['needed_by_date' => $neededByError]);
}

$storeProductQuantities = [];
foreach ($items as $item) {
    $storeProductId = intval($item['store_product_id'] ?? 0);
    $quantity = intval($item['quantity'] ?? 0);
    if ($storeProductId <= 0 || $quantity <= 0) {
        Response::error('Invalid item: store product and quantity are required', 400);
    }
    if ($quantity > 1000) {
        Response::error('Quantity cannot exceed 1000 per item', 400);
    }
    if (isset($storeProductQuantities[$storeProductId])) {
        Response::error('Each product can only be added once per requisition.', 400);
    }
    $storeProductQuantities[$storeProductId] = ['quantity' => $quantity];
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM requisitions WHERE id = ? FOR UPDATE");
    $stmt->execute([$requisitionId]);
    $req = $stmt->fetch();

    if (!$req) {
        $db->rollBack();
        Response::error('Requisition not found', 404);
    }
    if (!in_array($req['status'], ['revision_requested', 'budget_rejected'], true)) {
        $db->rollBack();
        Response::error('This requisition is not awaiting a resubmission. Current status: ' . $req['status'], 400);
    }
    if ((int)$req['requested_by'] !== (int)Auth::userId() && !Auth::isSuperAdmin()) {
        $db->rollBack();
        Response::forbidden('You can only resubmit your own requisitions.');
    }

    $supplierProductModel = new SupplierProduct();
    $eligible = $supplierProductModel->getEligibleSuppliers($storeProductQuantities);
    $chosen = null;
    foreach ($eligible as $candidate) {
        if ($candidate['supplier_id'] === $supplierId) {
            $chosen = $candidate;
            break;
        }
    }
    if (!$chosen) {
        $db->rollBack();
        Response::error('The selected supplier can no longer supply all of these items in the requested quantities. Please re-check the supplier comparison.', 400);
    }

    $db->prepare("DELETE FROM requisition_items WHERE requisition_id = ?")->execute([$requisitionId]);

    $reqModel = new Requisition();
    $subtotal = 0;
    foreach ($chosen['items'] as $line) {
        $total = $reqModel->addItem($requisitionId, $line['store_product_id'], $line['supplier_product_id'], $line['quantity'], $line['unit_price']);
        $subtotal += $total;
    }

    $stmt = $db->prepare("
        UPDATE requisitions
        SET preferred_supplier_id = ?, needed_by_date = ?, notes = ?, subtotal = ?, status = 'pending_budget_check', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$supplierId, $neededByDate !== '' ? $neededByDate : null, $notes, $subtotal, $requisitionId]);

    $eventMsg = "Resubmitted by Store Manager. Suggested supplier: {$chosen['supplier_name']}.";
    if ($supplierNote !== '') {
        $eventMsg .= " Note: {$supplierNote}";
    }
    (new RequisitionEvent())->log($requisitionId, 'requisition_resubmitted', $eventMsg, Auth::userId(), 'store_manager');

    $db->commit();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'requisition_pending_budget_check',
            "Requisition {$req['requisition_number']} was resubmitted (₱" . number_format($subtotal, 2) . ") and needs a budget check.",
            "?page=finance_staff_requisitions"
        );
    }

    Response::success(['requisition_id' => $requisitionId, 'status' => 'pending_budget_check', 'total' => $subtotal], 'Requisition resubmitted for budget check.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('requisitions/resubmit.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
