<?php
// app/handlers/store_manager/requisitions/create.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Requisition.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Requisition;
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

    $db->beginTransaction();

    $reqModel = new Requisition();
    $requisitionNumber = $reqModel->generateNumber();
    $requisitionId = $reqModel->create([
        'requisition_number' => $requisitionNumber,
        'requested_by' => Auth::userId(),
        'department_id' => $department['id'],
        'preferred_supplier_id' => $supplierId,
        'period_key' => CutoffPeriod::getCurrentKey(),
        'order_date' => $orderDate,
        'needed_by_date' => $neededByDate !== '' ? $neededByDate : null,
        'notes' => $notes,
    ]);

    $subtotal = 0;
    foreach ($items as $item) {
        $total = $reqModel->addItem(
            $requisitionId,
            intval($item['store_product_id']),
            intval($item['supplier_product_id']),
            intval($item['quantity']),
            floatval($item['unit_price']),
            isset($item['notes']) ? trim($item['notes']) : null
        );
        $subtotal += $total;
    }

    $stmt = $db->prepare("UPDATE requisitions SET subtotal = ? WHERE id = ?");
    $stmt->execute([$subtotal, $requisitionId]);

    $db->commit();

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'requisition_pending_budget_check',
            "New requisition #{$requisitionNumber} (₱" . number_format($subtotal, 2) . ") needs a budget check.",
            "?page=finance_staff_requisitions"
        );
    }

    Response::success([
        'requisition_id' => $requisitionId,
        'requisition_number' => $requisitionNumber,
        'total' => $subtotal
    ], 'Requisition created and sent for budget check');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('requisitions/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
