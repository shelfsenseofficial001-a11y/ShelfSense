<?php
// app/handlers/finance/head/requisitions/request_revision.php
// Finance Head's "Request Revision" action (recommend-and-approve model):
// instead of approving or outright rejecting, send the requisition back to
// the Store Manager with a reason -- they can change the suggested
// supplier/items and resubmit (store_manager/requisitions/resubmit.php).

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
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid request', 400);
}
if ($reason === '') {
    Response::error('A reason is required to request a revision.', 400);
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
    if ($req['status'] !== 'pending_finance_head') {
        $db->rollBack();
        Response::error('This requisition is not awaiting Finance Head review. Current status: ' . $req['status'], 400);
    }

    $reqModel = new Requisition();
    $reqModel->updateStatus($requisitionId, 'revision_requested');
    (new RequisitionEvent())->log($requisitionId, 'revision_requested', $reason, Auth::userId(), 'finance_head');

    createNotification(
        $req['requested_by'],
        'requisition_revision_requested',
        "Requisition {$req['requisition_number']} needs a revision. Reason: " . $reason,
        "?page=store_manager_requisitions"
    );

    $db->commit();
    Response::success(['requisition_id' => $requisitionId, 'status' => 'revision_requested'], 'Revision requested.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('finance/head/requisitions/request_revision.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
