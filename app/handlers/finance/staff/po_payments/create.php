<?php
// app/handlers/finance/staff/po_payments/create.php
// Finance Staff requests payment for a confirmed PO -- PO -> pending_payment.
// No budget ledger movement here yet (the reservation was already made when
// the requisition was approved); that only converts to a real expense once
// Finance Head approves this request.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/PoPaymentRequest.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoPaymentRequest;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if ($poId <= 0) {
    Response::error('Invalid purchase order id', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();
    if (!$po) {
        $db->rollBack();
        Response::notFound('Purchase order not found');
    }
    if ($po['status'] !== 'confirmed') {
        $db->rollBack();
        Response::error('This purchase order is not confirmed yet. Current status: ' . $po['status'], 400);
    }

    $paymentModel = new PoPaymentRequest();
    if ($paymentModel->getPendingForPo($poId)) {
        $db->rollBack();
        Response::error('A payment request is already pending for this purchase order.', 400);
    }

    $requestId = $paymentModel->create($poId, Auth::userId(), $po['total'], $notes);
    $poModel = new PurchaseOrder();
    $poModel->updateStatus($poId, 'pending_payment');

    $eventModel = new PoEvent();
    $eventModel->log($poId, 'payment_requested', "Payment of ₱" . number_format($po['total'], 2) . " requested by Finance Staff.", 'all', Auth::userId());

    foreach (getUsersByRole('finance_head') as $u) {
        createNotification($u['user_id'], 'po_payment_requested', "Payment requested for PO {$po['po_number']} (₱" . number_format($po['total'], 2) . ").", "?page=finance_head_payment_requests");
    }

    $db->commit();

    Response::success(['payment_request_id' => $requestId, 'po_id' => $poId, 'status' => 'pending_payment'], 'Payment requested. Awaiting Finance Head approval.');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('po_payments/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
