<?php
// app/handlers/store_manager/requisitions/create.php
// Store Manager builds a resupply request by picking store products; the
// eligible-supplier list (see purchase_orders/list_eligible_suppliers.php)
// narrows to only suppliers who carry every selected product with enough
// quantity on file. Once a supplier is chosen, this creates BOTH the
// requisition (an audit record of what was asked, by whom) and the Purchase
// Order itself immediately, priced from that supplier's on-file prices --
// no separate Finance-driven PO-creation step exists anymore.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Requisition.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/SupplierProduct.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\SupplierProduct;
use App\Models\Budget;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : 0;
$departmentId = isset($input['department_id']) ? intval($input['department_id']) : 0;
$orderDate = isset($input['order_date']) ? trim($input['order_date']) : '';
$neededByDate = isset($input['needed_by_date']) ? trim($input['needed_by_date']) : '';
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
if (strlen($notes) > 500) {
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
    if ($quantity > 999) {
        Response::error('Quantity cannot exceed 999 per item', 400);
    }
    $storeProductQuantities[$storeProductId] = ['quantity' => $quantity];
}

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();

    if ($departmentId > 0) {
        $department = $budgetModel->getDepartmentById($departmentId);
    } else {
        $department = $budgetModel->getDepartmentByName('store');
        if (!$department) {
            $newId = $budgetModel->createDepartment('store', 'STORE');
            $department = $budgetModel->getDepartmentById($newId);
        }
    }
    if (!$department) {
        Response::error('Invalid department', 400);
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
        'status' => 'converted_to_po',
        'order_date' => $orderDate,
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

    $poModel = new PurchaseOrder();
    $poNumber = $poModel->generateNumber();
    $poId = $poModel->create([
        'po_number' => $poNumber,
        'requisition_id' => $requisitionId,
        'supplier_id' => $supplierId,
        'status' => 'pending_budget_check',
        'order_date' => $orderDate,
        'expected_delivery_date' => $neededByDate !== '' ? $neededByDate : null,
        'created_by' => Auth::userId(),
    ]);
    foreach ($chosen['items'] as $line) {
        $poModel->addItem($poId, $line['store_product_id'], $line['supplier_product_id'], $line['quantity'], $line['unit_price']);
    }
    $poModel->recalculateTotals($poId);

    (new PoEvent())->log($poId, 'po_created', "Purchase Order {$poNumber} created by Store Manager from requisition #{$requisitionNumber}, supplier: {$chosen['supplier_name']}.", 'all', Auth::userId());

    $db->commit();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'po_pending_budget_check',
            "New Purchase Order {$poNumber} (₱" . number_format($subtotal, 2) . ") needs a budget check.",
            "?page=finance_staff_requisitions"
        );
    }

    Response::success([
        'requisition_id' => $requisitionId,
        'requisition_number' => $requisitionNumber,
        'po_id' => $poId,
        'po_number' => $poNumber,
        'total' => $subtotal
    ], 'Purchase Order created and sent for budget check');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('requisitions/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
