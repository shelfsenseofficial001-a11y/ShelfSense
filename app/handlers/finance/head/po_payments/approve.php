<?php
// app/handlers/finance/head/po_payments/approve.php
// Approve: posts the real budget expense + releases the requisition's
// reservation, records the actual payment, PO -> paid (a terminal/closing
// state now -- delivery already happened before payment). Reject: PO
// reverts to whatever it was before the payment request (received or
// partially_received, not confirmed -- delivery already happened), no
// budget movement (nothing was ever expensed).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../core/Mailer.php';
require_once __DIR__ . '/../../../../models/PoPaymentRequest.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/Budget.php';
require_once __DIR__ . '/../../../../models/Payment.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Mailer;
use App\Models\PoPaymentRequest;
use App\Models\PurchaseOrder;
use App\Models\Budget;
use App\Models\Payment;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requestId = isset($input['payment_request_id']) ? intval($input['payment_request_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$method = isset($input['method']) ? trim($input['method']) : 'bank_transfer';
$referenceNumber = isset($input['reference_number']) ? trim($input['reference_number']) : ('PO-PAY-' . date('YmdHis'));

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $reason === '') {
    Response::error('Rejection reason is required', 400);
}
if (!in_array($method, ['bank_transfer', 'check', 'cash', 'other'], true)) {
    Response::error('Invalid payment method', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM po_payment_requests WHERE id = ? FOR UPDATE");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        $db->rollBack();
        Response::notFound('Payment request not found');
    }
    if ($request['status'] !== 'pending') {
        $db->rollBack();
        Response::error('This payment request has already been ' . $request['status'] . '.', 400);
    }

    $stmt = $db->prepare("SELECT po.*, r.department_id, r.period_key, r.id as requisition_id FROM purchase_orders po JOIN requisitions r ON po.requisition_id = r.id WHERE po.id = ? FOR UPDATE");
    $stmt->execute([$request['po_id']]);
    $po = $stmt->fetch();

    $paymentRequestModel = new PoPaymentRequest();
    $poModel = new PurchaseOrder();
    $eventModel = new PoEvent();

    if ($action === 'reject') {
        $paymentRequestModel->updateStatus($requestId, 'rejected', Auth::userId(), $reason);
        $poModel->updateStatus($po['id'], $poModel->determineReceivedStatus($po['id']));
        $eventModel->log($po['id'], 'payment_rejected', "Payment request rejected by Finance Head. Reason: $reason", 'all', Auth::userId());

        createNotification($request['requested_by'], 'po_payment_rejected', "Payment request for PO {$po['po_number']} was rejected. Reason: $reason", "?page=finance_staff_payment_requests");

        $db->commit();
        Response::success(['payment_request_id' => $requestId, 'status' => 'rejected'], 'Payment request rejected.');
        exit;
    }

    // approve
    $budgetModel = new Budget();
    $amount = (float)$request['amount'];

    $budgetModel->postTransaction($po['department_id'], $po['period_key'], 'expense', $amount, 'purchase_order', $po['id'], Auth::userId(), "PO payment request #{$requestId}");
    $openReservation = $budgetModel->getOpenReservation('requisition', $po['requisition_id']);
    if ($openReservation > 0) {
        $budgetModel->postTransaction($po['department_id'], $po['period_key'], 'release', $openReservation, 'requisition', $po['requisition_id'], Auth::userId(), "Reservation closed by PO payment request #{$requestId}");
    }

    $paymentRequestModel->updateStatus($requestId, 'approved', Auth::userId());
    $poModel->updateStatus($po['id'], 'paid');

    $paymentModel = new Payment();
    $paymentId = $paymentModel->createForPo($po['id'], $requestId, $amount, $method, $referenceNumber, Auth::userId());

    $eventModel->log($po['id'], 'payment_approved', "Payment of ₱" . number_format($amount, 2) . " approved by Finance Head.", 'all', Auth::userId());

    $stmt = $db->prepare("SELECT company_name, email FROM suppliers WHERE id = ?");
    $stmt->execute([$po['supplier_id']]);
    $supplier = $stmt->fetch();

    if (!empty($supplier['email'])) {
        $mailer = new Mailer('procurement');
        $body = "
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>Payment Sent</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;font-family:Arial,sans-serif;'>
                <p>Dear <strong>{$supplier['company_name']}</strong>,</p>
                <p>Payment for Purchase Order <strong>{$po['po_number']}</strong> has been approved and sent.</p>
                <p><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</p>
                <p><strong>Method:</strong> " . strtoupper($method) . "</p>
                <p><strong>Reference:</strong> {$referenceNumber}</p>
                <p style='margin-top:16px;'>This closes out the order. Thank you for your business.</p>
            </div>
        ";
        $mailer->send($supplier['email'], "Payment Sent - PO {$po['po_number']}", $body);
    }
    $paymentModel->markRemittanceSent($paymentId);

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$supplier['email']]);
    $supplierUser = $stmt->fetch();
    if ($supplierUser) {
        createNotification($supplierUser['user_id'], 'po_payment_received', "Payment for PO {$po['po_number']} has been sent.", "?page=supplier_requisitions");
    }
    createNotification($request['requested_by'], 'po_payment_approved', "Payment request for PO {$po['po_number']} was approved and disbursed.", "?page=finance_staff_payment_requests");

    $db->commit();

    Response::success(['payment_request_id' => $requestId, 'po_id' => $po['id'], 'status' => 'paid'], 'Payment approved and sent to supplier.');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('po_payments/approve.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
