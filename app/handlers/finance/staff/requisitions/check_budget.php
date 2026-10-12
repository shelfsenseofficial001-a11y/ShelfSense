<?php
// app/handlers/finance/staff/requisitions/check_budget.php
// Finance Staff pass/reject a Requisition's budget check -- the first gate
// now that Requisition and Purchase Order are separate stages again. Passing
// forwards it to Finance Head for the supplier-approval/revision-request
// gate (see finance/head/requisitions/approve.php); no PO or budget
// reservation exists yet at this point.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Requisition.php';
require_once __DIR__ . '/../../../../models/RequisitionEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\RequisitionEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($requisitionId <= 0 || !in_array($action, ['pass', 'reject'], true)) {
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

    $stmt = $db->prepare("SELECT * FROM requisitions WHERE id = ? FOR UPDATE");
    $stmt->execute([$requisitionId]);
    $req = $stmt->fetch();

    if (!$req) {
        $db->rollBack();
        Response::error('Requisition not found', 404);
    }
    if ($req['status'] !== 'pending_budget_check') {
        $db->rollBack();
        Response::error('This requisition is not awaiting a budget check. Current status: ' . $req['status'], 400);
    }

    $reqModel = new Requisition();

    if ($action === 'pass') {
        $reqModel->updateStatus($requisitionId, 'pending_finance_head');
        (new RequisitionEvent())->log($requisitionId, 'budget_check_passed', 'Passed budget check by Finance Staff.', Auth::userId(), 'finance_staff');

        foreach (getUsersByRole('finance_head') as $u) {
            createNotification(
                $u['user_id'],
                'requisition_pending_approval',
                "Requisition {$req['requisition_number']} passed budget check and needs your review.",
                "?page=finance_head_requisitions"
            );
        }

        $db->commit();
        Response::success(['requisition_id' => $requisitionId, 'status' => 'pending_finance_head'], 'Budget check passed. Forwarded to Finance Head.');
    } else {
        $reqModel->updateStatus($requisitionId, 'budget_rejected', ['rejected_reason' => $reason]);
        (new RequisitionEvent())->log($requisitionId, 'budget_check_rejected', "Rejected for budget reasons by Finance Staff. Reason: $reason", Auth::userId(), 'finance_staff');

        createNotification(
            $req['requested_by'],
            'requisition_budget_rejected',
            "Requisition {$req['requisition_number']} failed budget check. Reason: " . $reason,
            "?page=store_manager_requisitions"
        );

        $db->commit();
        Response::success(['requisition_id' => $requisitionId, 'status' => 'budget_rejected'], 'Requisition rejected for budget reasons.');
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('finance/staff/requisitions/check_budget.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
