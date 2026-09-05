<?php
// app/handlers/finance/head/requisitions/approve.php
// Approve: posts a 'reservation' ledger entry and auto-generates the PO
// (status pending_dispatch) from the requisition's items. Reject: no budget
// movement at all (nothing was ever reserved for a requisition that hadn't
// been approved yet).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Requisition.php';
require_once __DIR__ . '/../../../../models/Budget.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\Budget;
use App\Models\PurchaseOrder;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$justification = isset($input['justification']) ? trim($input['justification']) : '';

if ($requisitionId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $reason === '') {
    Response::error('Rejection reason is required', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM requisitions WHERE id = ? FOR UPDATE");
    $stmt->execute([$requisitionId]);
    $requisition = $stmt->fetch();

    if (!$requisition) {
        $db->rollBack();
        Response::error('Requisition not found', 404);
    }
    if ($requisition['status'] !== 'pending_finance_head') {
        $db->rollBack();
        Response::error('This requisition is not awaiting Finance Head approval. Current status: ' . $requisition['status'], 400);
    }

    $reqModel = new Requisition();

    if ($action === 'reject') {
        $reqModel->updateStatus($requisitionId, 'rejected', ['rejected_reason' => $reason]);

        createNotification(
            $requisition['requested_by'],
            'requisition_rejected',
            "Requisition #{$requisition['requisition_number']} was rejected by Finance Head. Reason: " . $reason,
            "?page=store_manager_requisitions"
        );

        $db->commit();
        Response::success(['requisition_id' => $requisitionId, 'status' => 'rejected'], 'Requisition rejected.');
        exit;
    }

    // approve
    $budgetModel = new Budget();
    $amount = (float)$requisition['subtotal'];
    $budgetStatus = $budgetModel->getBudgetStatus($requisition['department_id'], $requisition['period_key'], $amount, 'requisition', $requisitionId);

    if ($budgetStatus['exceeded'] && $justification === '') {
        $db->rollBack();
        Response::error(
            'This request exceeds the available budget (short by ₱' . number_format($budgetStatus['shortfall'], 2) . '). A justification is required to approve over budget.',
            400,
            ['justification' => 'Justification is required for over-budget approval.']
        );
    }

    $budgetModel->postTransaction(
        $requisition['department_id'],
        $requisition['period_key'],
        'reservation',
        $amount,
        'requisition',
        $requisitionId,
        Auth::userId(),
        $justification !== '' ? $justification : null
    );

    $reqModel->updateStatus($requisitionId, 'converted_to_po');

    $items = $reqModel->getItems($requisitionId);
    $poModel = new PurchaseOrder();
    $poNumber = $poModel->generateNumber();
    $poId = $poModel->create([
        'po_number' => $poNumber,
        'requisition_id' => $requisitionId,
        'supplier_id' => $requisition['preferred_supplier_id'],
        'order_date' => date('Y-m-d'),
        'expected_delivery_date' => $requisition['needed_by_date'],
        'created_by' => Auth::userId(),
    ]);
    foreach ($items as $item) {
        $poModel->addItem($poId, $item['store_product_id'], $item['supplier_product_id'], $item['quantity'], $item['estimated_unit_price']);
    }
    $poModel->recalculateTotals($poId);

    (new PoEvent())->log($poId, 'po_created', "Purchase Order {$poNumber} created from requisition #{$requisition['requisition_number']} on Finance Head approval." . ($justification !== '' ? " Justification: $justification" : ''), 'all', Auth::userId());

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'po_pending_dispatch',
            "Purchase Order {$poNumber} (from requisition #{$requisition['requisition_number']}) is ready to dispatch to the supplier.",
            "?page=finance_staff_payment_requests"
        );
    }
    createNotification(
        $requisition['requested_by'],
        'requisition_approved',
        "Requisition #{$requisition['requisition_number']} was approved. Purchase Order {$poNumber} has been created.",
        "?page=store_manager_requisitions"
    );

    $db->commit();

    Response::success([
        'requisition_id' => $requisitionId,
        'status' => 'converted_to_po',
        'po_id' => $poId,
        'po_number' => $poNumber,
        'budget_exceeded' => $budgetStatus['exceeded'],
    ], 'Requisition approved. Purchase Order created.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('finance/head/requisitions/approve.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
