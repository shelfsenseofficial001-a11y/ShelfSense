<?php
// app/handlers/store_manager/requisitions/create.php
// Store Manager builds a resupply request: picks store products + quantities,
// and ONE suggested supplier from the eligible-supplier comparison (narrowed
// to suppliers who carry every selected product with enough quantity on
// file) plus an optional note on why that supplier. This creates ONLY the
// requisition (an audit record of what was asked, by whom) -- the Purchase
// Order itself is no longer created here. It's created later by Finance Head
// at approval time (see finance/head/requisitions/approve.php), once Finance
// Staff has budget-checked the requisition and Finance Head has signed off on
// the suggested supplier (or sent it back for revision).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Requisition.php';
require_once __DIR__ . '/../../../models/RequisitionEvent.php';
require_once __DIR__ . '/../../../models/SupplierProduct.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Requisition;
use App\Models\RequisitionEvent;
use App\Models\SupplierProduct;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : 0;
$neededByDate = isset($input['needed_by_date']) ? trim($input['needed_by_date']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$supplierNote = isset($input['supplier_note']) ? trim($input['supplier_note']) : '';
$items = isset($input['items']) ? $input['items'] : [];

if ($supplierId <= 0) {
    Response::error('A suggested supplier is required', 400);
}
if (empty($items) || !is_array($items)) {
    Response::error('At least one item is required', 400);
}
if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}
if (strlen($supplierNote) > 500) {
    Response::error('Supplier note cannot exceed 500 characters', 400);
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

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();

    // Procurement budget lives entirely under the Store/Front department --
    // never client-selectable.
    $department = $budgetModel->getDepartmentByName('store');
    if (!$department) {
        $newId = $budgetModel->createDepartment('store', 'STORE');
        $department = $budgetModel->getDepartmentById($newId);
    }

    // Re-validate eligibility server-side -- the client's comparison view is
    // just a convenience; availability/quantity may have changed since.
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
        Response::error('The selected supplier can no longer supply all of these items in the requested quantities. Please re-check the supplier comparison.', 400);
    }

    $db->beginTransaction();

    $reqModel = new Requisition();
    $requisitionNumber = $reqModel->generateNumber();
    $requisitionId = $reqModel->create([
        'requisition_number' => $requisitionNumber,
        'requested_by' => Auth::userId(),
        'department_id' => $department['id'],
        'preferred_supplier_id' => $supplierId,
        'period_key' => CutoffPeriod::getCurrentKey(),
        'status' => 'pending_budget_check',
        'order_date' => date('Y-m-d'),
        'needed_by_date' => $neededByDate !== '' ? $neededByDate : null,
        'notes' => $notes,
    ]);

    $subtotal = 0;
    foreach ($chosen['items'] as $line) {
        $total = $reqModel->addItem(
            $requisitionId,
            $line['store_product_id'],
            $line['supplier_product_id'],
            $line['quantity'],
            $line['unit_price']
        );
        $subtotal += $total;
    }
    $stmt = $db->prepare("UPDATE requisitions SET subtotal = ? WHERE id = ?");
    $stmt->execute([$subtotal, $requisitionId]);

    $eventMsg = "Requisition {$requisitionNumber} submitted. Suggested supplier: {$chosen['supplier_name']}.";
    if ($supplierNote !== '') {
        $eventMsg .= " Note: {$supplierNote}";
    }
    (new RequisitionEvent())->log($requisitionId, 'requisition_submitted', $eventMsg, Auth::userId(), 'store_manager');

    $db->commit();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'requisition_pending_budget_check',
            "New Requisition {$requisitionNumber} (₱" . number_format($subtotal, 2) . ") needs a budget check.",
            "?page=finance_staff_requisitions"
        );
    }

    Response::success([
        'requisition_id' => $requisitionId,
        'requisition_number' => $requisitionNumber,
        'total' => $subtotal
    ], 'Requisition submitted for budget check');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('requisitions/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
