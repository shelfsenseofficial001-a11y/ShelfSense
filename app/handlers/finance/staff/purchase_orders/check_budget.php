<?php
// app/handlers/finance/staff/purchase_orders/check_budget.php
// Finance Staff pass/reject a Purchase Order's budget check (moved off the
// requisition -- the PO is created directly by the Store Manager now, with
// exact prices, so the budget check applies to the PO itself). Passing
// forwards it to Finance Head for the explicit PO-approval gate (no
// reservation posted yet -- that only happens if/when Finance Head approves).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($poId <= 0 || !in_array($action, ['pass', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $reason === '') {
    Response::error('Rejection reason is required', 400);
}
if (strlen($reason) > 500) {
    Response::error('Reason cannot exceed 500 characters', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT po.*, r.requested_by, r.requisition_number FROM purchase_orders po JOIN requisitions r ON po.requisition_id = r.id WHERE po.id = ? FOR UPDATE");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();

    if (!$po) {
        $db->rollBack();
        Response::error('Purchase order not found', 404);
    }
    if ($po['status'] !== 'pending_budget_check') {
        $db->rollBack();
        Response::error('This purchase order is not awaiting a budget check. Current status: ' . $po['status'], 400);
    }

    $poModel = new PurchaseOrder();

    if ($action === 'pass') {
        $poModel->updateStatus($poId, 'pending_fh_approval');

        foreach (getUsersByRole('finance_head') as $u) {
            createNotification(
                $u['user_id'],
                'po_pending_approval',
                "Purchase Order {$po['po_number']} passed budget check and needs your approval.",
                "?page=finance_head_requisitions"
            );
        }

        $db->commit();
        Response::success(['po_id' => $poId, 'status' => 'pending_fh_approval'], 'Budget check passed. Forwarded to Finance Head.');
    } else {
        $poModel->updateStatus($poId, 'budget_rejected', ['budget_rejected_reason' => $reason]);

        createNotification(
            $po['requested_by'],
            'po_budget_rejected',
            "Purchase Order {$po['po_number']} (requisition #{$po['requisition_number']}) failed budget check. Reason: " . $reason,
            "?page=store_manager_requisitions"
        );

        $db->commit();
        Response::success(['po_id' => $poId, 'status' => 'budget_rejected'], 'Purchase Order rejected for budget reasons.');
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('check_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
