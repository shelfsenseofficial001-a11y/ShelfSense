<?php
// app/handlers/finance/head/purchase_orders/approve.php
// The explicit Finance Head PO-approval gate (previously implicit inside
// requisition approval -- now a distinct step since the PO is created
// directly by the Store Manager). Approve: posts the 'reservation' budget
// ledger entry (moved here from the old requisition-approval step) and
// forwards the PO to dispatch. Reject: no budget movement (nothing was ever
// reserved for a PO that hadn't been approved yet); the requisition is
// cancelled too so the Store Manager can start over with a different supplier.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Requisition.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/Budget.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\Budget;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$justification = isset($input['justification']) ? trim($input['justification']) : '';

if ($poId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $reason === '') {
    Response::error('Rejection reason is required', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT po.*, r.id as requisition_id, r.requisition_number, r.requested_by, r.department_id, r.period_key
        FROM purchase_orders po
        JOIN requisitions r ON po.requisition_id = r.id
        WHERE po.id = ? FOR UPDATE
    ");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();

    if (!$po) {
        $db->rollBack();
        Response::error('Purchase order not found', 404);
    }
    if ($po['status'] !== 'pending_fh_approval') {
        $db->rollBack();
        Response::error('This purchase order is not awaiting Finance Head approval. Current status: ' . $po['status'], 400);
    }

    $poModel = new PurchaseOrder();
    $reqModel = new Requisition();

    if ($action === 'reject') {
        $poModel->updateStatus($poId, 'cancelled', ['rejection_reason' => $reason]);
        $reqModel->updateStatus($po['requisition_id'], 'cancelled', ['rejected_reason' => $reason]);

        (new PoEvent())->log($poId, 'po_rejected', "Purchase Order {$po['po_number']} rejected by Finance Head. Reason: $reason", 'all', Auth::userId());

        createNotification(
            $po['requested_by'],
            'po_rejected',
            "Purchase Order {$po['po_number']} was rejected by Finance Head. Reason: " . $reason,
            "?page=store_manager_requisitions"
        );

        $db->commit();
        Response::success(['po_id' => $poId, 'status' => 'cancelled'], 'Purchase Order rejected.');
        exit;
    }

    // approve
    $budgetModel = new Budget();
    $amount = (float)$po['total'];
    $budgetStatus = $budgetModel->getBudgetStatus($po['department_id'], $po['period_key'], $amount, 'requisition', $po['requisition_id']);

    if ($budgetStatus['exceeded'] && $justification === '') {
        $db->rollBack();
        Response::error(
            'This PO exceeds the available budget (short by ₱' . number_format($budgetStatus['shortfall'], 2) . '). A justification is required to approve over budget.',
            400,
            ['justification' => 'Justification is required for over-budget approval.']
        );
    }

    $budgetModel->postTransaction(
        $po['department_id'],
        $po['period_key'],
        'reservation',
        $amount,
        'requisition',
        $po['requisition_id'],
        Auth::userId(),
        $justification !== '' ? $justification : null
    );

    $poModel->updateStatus($poId, 'pending_dispatch', [
        'approved_by' => Auth::userId(),
        'approved_at' => date('Y-m-d H:i:s'),
    ]);

    (new PoEvent())->log($poId, 'po_approved', "Purchase Order {$po['po_number']} approved by Finance Head." . ($justification !== '' ? " Justification: $justification" : ''), 'all', Auth::userId());

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'po_pending_dispatch',
            "Purchase Order {$po['po_number']} was approved and is ready to dispatch to the supplier.",
            "?page=finance_staff_payment_requests"
        );
    }
    createNotification(
        $po['requested_by'],
        'po_approved',
        "Purchase Order {$po['po_number']} (requisition #{$po['requisition_number']}) was approved by Finance Head.",
        "?page=store_manager_requisitions"
    );

    $db->commit();

    Response::success([
        'po_id' => $poId,
        'status' => 'pending_dispatch',
        'budget_exceeded' => $budgetStatus['exceeded'],
    ], 'Purchase Order approved.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('finance/head/purchase_orders/approve.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
